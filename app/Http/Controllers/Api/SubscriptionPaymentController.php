<?php

namespace App\Http\Controllers\Api;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\CompanySubscription;
use App\Models\Transaction;
use App\Models\Subscription;
use App\service\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubscriptionPaymentController extends Controller
{
    protected $paystack;

    public function __construct(PaystackService $paystack)
    {
        $this->paystack = $paystack;
    }

    public function verify(Request $request)
    {
        $request->validate([
            'reference' => 'required|string',
            'subscription_id' => 'required|exists:subscriptions,id',
        ]);

        $user = Auth::user();
        $subscription = Subscription::fromUlid($request->subscription_id);
        $company = $user->company;

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'User does not belong to any company.'
            ], 422);
        }

        // Verify payment via Paystack
        $payment = $this->paystack->verifyPayment($request->reference);

        if (!$payment || !isset($payment['status']) || $payment['status'] !== 'success') {
            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed.'
            ], 422);
        }

        // Store transaction
        $now = now();
        DB::beginTransaction();
        try {
            $transaction = Transaction::create([
                'company_id' => $company->id,
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'reference' => $payment['reference'],
                'amount' => $payment['amount'] / 100,
                'currency' => $payment['currency'] ?? 'NGN',
                'status' => 'success',
                'payment_gateway' => 'paystack',
                'meta' => $payment,
            ]);

            $companySubscription = CompanySubscription::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'subscription_id' => $subscription->id,
                ],
                [
                    'start_date' => $now,
                    'end_date' => (clone $now)->addDays($subscription->duration_days),
                    'status' => SubscriptionStatus::ACTIVE,
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'transaction' => $transaction,
                'company_subscription' => $companySubscription,
                'message' => 'Subscription payment verified and stored successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
