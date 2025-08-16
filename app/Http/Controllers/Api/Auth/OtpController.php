<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Otp;
use App\Models\User;
use App\Notifications\SendOtpNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    /**
     * Resend OTP to a user
     */
    public function resend(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        // Generate new OTP
        $otpCode = mt_rand(100000, 999999);

        // Save OTP in DB
        $otp = Otp::updateOrCreate(
            ['user_id' => $user->id],
            [
                'code' => $otpCode,
                'expires_at' => Carbon::now()->addMinutes(10),
            ]
        );

        // Send OTP notification
        $user->notify(new SendOtpNotification($otpCode));

        return response()->json([
            'message' => 'OTP resent successfully.',
        ]);
    }

    /**
     * Verify OTP
     */
    public function verify(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:6',
        ]);

        $user = User::where('email', $request->email)->first();

        $otp = Otp::where('user_id', $user->id)
            ->where('code', $request->otp)
            ->first();

        if (!$otp) {
            return response()->json([
                'message' => 'Invalid OTP.'
            ], 400);
        }

        if (Carbon::now()->greaterThan($otp->expires_at)) {
            return response()->json([
                'message' => 'OTP expired.'
            ], 400);
        }

        // Mark email as verified
        $user->email_verified_at = now();
        $user->save();

        // Delete OTP after verification
        $otp->delete();

        // Create API token (Sanctum)
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'OTP verified successfully.',
            'token' => $token,
            'user' => new UserResource($user->load('company')),
        ]);
    }
}
