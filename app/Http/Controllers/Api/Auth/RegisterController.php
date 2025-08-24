<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\Company;
use App\Models\Otp;
use App\Models\User;
use App\Notifications\SendOtpNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class RegisterController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        DB::beginTransaction();
        try {
            // Create company
            $company = Company::create([
                'name' => $data['company']['name'],
                'email' => $data['company']['email'],
                'phone' => $data['company']['phone'],
            ]);

            // Create user
            $userData = $data['user'];

            $path = null;

            if ($request->hasFile('user.avatar')) {
                $file = $request->file('user.avatar');
                $path = $file->store('avatars', 'public');
            }

            $user = User::create([
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'company_id' => $company->id,
                'email_verified_at' => null,
                'avatar_url' => $path,
                'role' => UserRole::ADMIN,
            ]);

            $company->admin_id = $user->id;
            $company->save();

            // Generate OTP
            $otpCode = mt_rand(100000, 999999);

            $otp = Otp::create([
                'user_id' => $user->id,
                'code' => $otpCode,
                'expires_at' => Carbon::now()->addMinutes(10),
            ]);

            // Send OTP notification
            $user->notify(new SendOtpNotification($otpCode));
            DB::commit();

            return response()->json([
                'message' => 'Registration successful. Please check your email for OTP.',
                'user' => $user->only('first_name', 'last_name', 'email'),
                'company' => $company->only('name', 'email', 'phone'),
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error Registering User:" . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => "Registration Failed"
            ]);
        }
    }
}
