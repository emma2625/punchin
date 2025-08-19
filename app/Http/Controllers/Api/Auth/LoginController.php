<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::with(['company'])
            ->where('email', $request->email)
            ->first();

        // dd($user);

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'error' => ['message' => 'Invalid credentials.']
            ], 401);
        }

        if (is_null($user->email_verified_at)) {
            return response()->json([
                'success' => false,
                'email_verified' => false,
                'error' => ['message' => 'Email not verified. Please verify your email.']
            ], 403);
        }

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        $user->load([
            'company',
            'company.activeSubscription',
        ]);

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => new UserResource($user)
        ]);
    }
}
