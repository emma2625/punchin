<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Update authenticated user profile.
     * Only allows first_name and last_name to be updated.
     */
    public function updateUserProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user->update($validator->validated());

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
