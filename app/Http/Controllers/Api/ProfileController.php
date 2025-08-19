<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Update authenticated user profile.
     * Allows first_name, last_name, and avatar_url to be updated.
     */
    public function updateUserProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'avatar' => 'nullable|file|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $data = $validator->validated();
        $oldAvatarPath = null;

        // Handle avatar upload if present
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $path = $file->store('avatars', 'public'); // uses hashName automatically
            $data['avatar_url'] = $path; // store just the path without 'storage/'

            // store old avatar path to delete later
            if ($user->avatar_url) {
                $oldAvatarPath = $user->avatar_url;
            }
        }

        // Update user
        $user->update($data);

        // Delete old avatar after successful update
        if ($oldAvatarPath && Storage::disk('public')->exists($oldAvatarPath)) {
            Storage::disk('public')->delete($oldAvatarPath);
        }

        return response()->json([
            'success' => true,
            'data' => $user->fresh(),
        ]);
    }


    /**
     * Update company profile.
     * Only allows name, email, and phone to be updated.
     * Only the company admin can update their company.
     */
    public function updateCompanyProfile(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found for this user.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $company->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $company->fresh(),
        ]);
    }
}
