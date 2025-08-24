<?php

namespace App\Http\Controllers\Api;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\CompanySubscription;
use App\Models\Transaction;
use App\Models\Subscription;
use App\service\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

use function Laravel\Prompts\error;

class SubscriptionPaymentController extends Controller
{
    protected $paystack;

    public function __construct(PaystackService $paystack)
    {
        $this->paystack = $paystack;
    }

    /**
     * Initialize a subscription payment
     */
    public function initialize(Request $request)
    {
        $request->validate([
            'subscription_id' => 'required|exists:subscriptions,ulid',
            'months' => 'required|integer|min:1|max:12'
        ]);

        try {
            $user = $request->user();
            $subscription = Subscription::fromUlid($request->subscription_id);
            $company = $user->company;
            $months = (int) $request->months;

            if (!$company) {
                Log::warning("User {$user->id} does not belong to any company.");
                return response()->json([
                    'success' => false,
                    'message' => 'User does not belong to any company.'
                ], 422);
            }

            // Calculate total amount based on months
            $monthlyPrice = $subscription->price;
            $totalAmount = $monthlyPrice * $months;

            // Initialize payment via Paystack
            $email = $user->email;
            $callbackUrl = route('subscriptions.verify'); // optional
            $metadata = [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'subscription_id' => $subscription->id,
                'months' => $months,
                'monthly_price' => $monthlyPrice,
                'total_amount' => $totalAmount,
            ];

            Log::debug('Initializing Paystack payment', [
                'email' => $email,
                'amount' => $totalAmount,
                'months' => $months,
                'monthly_price' => $monthlyPrice,
                'callback_url' => $callbackUrl,
                'metadata' => $metadata,
            ]);

            $payment = $this->paystack->initializePayment($email, $totalAmount, $callbackUrl, $metadata);

            Log::debug('Paystack initialize response', [
                'response' => $payment,
            ]);

            if (!$payment || !isset($payment['status']) || !$payment['status']) {
                Log::error('Paystack initialization failed', [
                    'payment_response' => $payment
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to initialize payment.'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'access_code' => $payment['data']['access_code'] ?? null,
                'authorization_url' => $payment['data']['authorization_url'] ?? null,
                'reference' => $payment['data']['reference'] ?? null,
                'amount' => $totalAmount,
                'months' => $months,
            ]);
        } catch (\Throwable $th) {
            Log::error('Error initializing payment: ' . $th->getMessage(), [
                'stack' => $th->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Sorry, failed to initialize payment.',
            ], 500);
        }
    }

    /**
     * Verify subscription payment
     */
    public function verify(Request $request)
    {
        $request->validate([
            'reference' => 'required|string',
            'subscription_id' => 'required|exists:subscriptions,ulid',
            'months' => 'sometimes|integer|min:1|max:12'
        ]);

        $user = Auth::user();
        $subscription = Subscription::fromUlid($request->subscription_id);
        $company = $user->company;

        // Log::debug('Verifying subscription payment', [
        //     'user_id' => $user->id ?? null,
        //     'subscription_id' => $request->subscription_id,
        //     'company_id' => $company->id ?? null,
        //     'reference' => $request->reference,
        //     'months' => $request->months ?? 'not provided',
        // ]);

        if (!$company) {
            Log::debug('User has no company assigned', ['user_id' => $user->id]);
            return response()->json([
                'success' => false,
                'message' => 'User does not belong to any company.'
            ], 422);
        }

        // Verify payment via Paystack
        $paymentVerification = $this->paystack->verifyPayment($request->reference);
        // Log::debug('Paystack payment verification response', ['payment' => $paymentVerification]);

        if (
            !$paymentVerification ||
            !isset($paymentVerification['data']['status']) ||
            $paymentVerification['data']['status'] !== 'success'
        ) {
            // Log::debug('Payment verification failed', ['payment' => $paymentVerification]);
            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed.'
            ], 422);
        }

        $paymentData = $paymentVerification['data'];

        // Extract months from metadata or request
        $months = 1; // default
        if (isset($paymentData['metadata']['months'])) {
            $months = (int) $paymentData['metadata']['months'];
        } elseif ($request->has('months')) {
            $months = (int) $request->months;
        }

        // Validate months
        if ($months < 1 || $months > 12) {
            $months = 1;
            Log::warning('Invalid months value, defaulting to 1', ['original_months' => $request->months ?? 'unknown']);
        }

        // Log::debug('Processing subscription for months', ['months' => $months]);

        DB::beginTransaction();

        try {
            // Create transaction record
            $transaction = Transaction::create([
                'company_id' => $company->id,
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'reference' => $paymentData['reference'],
                'amount' => $paymentData['amount'] / 100, // Convert from kobo to naira
                'currency' => $paymentData['currency'] ?? 'NGN',
                'status' => 'success',
                'payment_gateway' => 'paystack',
                'meta' => array_merge($paymentData, ['months_purchased' => $months]),
            ]);
            // Log::debug('Transaction created successfully', [
            //     'transaction_id' => $transaction->id,
            //     'amount' => $transaction->amount,
            //     'months' => $months
            // ]);

            // Calculate start and end dates
            $now = now();

            // Check if company has an active subscription
            $existingSubscription = CompanySubscription::where('company_id', $company->id)
                ->where('status', SubscriptionStatus::ACTIVE)
                ->first();

            if ($existingSubscription && $existingSubscription->end_date > $now) {
                // Extend existing subscription
                $startDate = Carbon::parse($existingSubscription->end_date);
                $endDate = (clone $startDate)->addMonths($months);

                // Log::debug('Extending existing subscription', [
                //     'current_end_date' => $existingSubscription->end_date,
                //     'new_start_date' => $startDate,
                //     'new_end_date' => $endDate,
                //     'months_added' => $months
                // ]);
            } else {
                // New subscription or expired subscription
                $startDate = $now;
                $endDate = (clone $startDate)->addMonths($months);

                // Log::debug('Creating new subscription period', [
                //     'start_date' => $startDate,
                //     'end_date' => $endDate,
                //     'months' => $months
                // ]);
            }

            // Create or update company subscription
            $companySubscription = CompanySubscription::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'subscription_id' => $subscription->id,
                ],
                [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => SubscriptionStatus::ACTIVE,
                    'months_purchased' => $months,
                    'last_payment_date' => $now,
                ]
            );

            // Log::debug('Company subscription updated/created', [
            //     'company_subscription_id' => $companySubscription->id,
            //     'start_date' => $companySubscription->start_date,
            //     'end_date' => $companySubscription->end_date,
            //     'months_purchased' => $months
            // ]);

            // Deactivate any other active subscriptions for this company
            CompanySubscription::where('company_id', $company->id)
                ->where('id', '!=', $companySubscription->id)
                ->where('status', SubscriptionStatus::ACTIVE)
                ->update(['status' => SubscriptionStatus::CANCELLED]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'transaction' => $transaction,
                    'company_subscription' => $companySubscription,
                    'subscription_details' => [
                        'plan_name' => $subscription->name,
                        'monthly_price' => $subscription->price,
                        'months_purchased' => $months,
                        'total_amount' => $transaction->amount,
                        'start_date' => $companySubscription->start_date,
                        'end_date' => $companySubscription->end_date,
                        'days_remaining' => $companySubscription->end_date->diffInDays($now),
                    ]
                ],
                'message' => "Subscription payment verified successfully. {$months} month(s) of {$subscription->name} plan activated."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment verification exception', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'reference' => $request->reference,
                'subscription_id' => $request->subscription_id,
                'months' => $months
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get subscription history for a company
     */
    public function getSubscriptionHistory(Request $request)
    {
        try {
            $user = $request->user();
            $company = $user->company;

            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'User does not belong to any company.'
                ], 422);
            }

            $transactions = Transaction::where('company_id', $company->id)
                ->with(['subscription'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            $activeSubscription = CompanySubscription::where('company_id', $company->id)
                ->where('status', SubscriptionStatus::ACTIVE)
                ->with('subscription')
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'active_subscription' => $activeSubscription,
                    'transaction_history' => $transactions,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching subscription history: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subscription history.'
            ], 500);
        }
    }
}
