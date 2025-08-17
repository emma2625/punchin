<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Notifications\StaffAccountCreated;

class StaffController extends Controller
{
    // Get all staff for the admin's company
    public function getStaff(Request $request)
    {
        $user = $request->user();

        if ($user->role !== UserRole::ADMIN) {
            return response()->json([
                'message' => 'Unauthorized. Only admins can view staff.'
            ], 403);
        }

        $company = $user->company()->first();
        if (!$company) {
            return response()->json([
                'message' => 'You do not belong to any company.'
            ], 404);
        }

        $staff = $company->staff()->get();

        return response()->json([
            'data' => $staff,
        ], 200);
    }

    // Add a new staff or attach existing staff
    public function addStaff(Request $request)
    {
        $user = $request->user();

        if ($user->role !== UserRole::ADMIN) {
            return response()->json([
                'message' => 'Unauthorized. Only admins can add staff.'
            ], 403);
        }

        $company = $user->company()->first();
        if (!$company || !$company->activeSubscription()->exists()) {
            return response()->json([
                'message' => 'You need an active subscription to add staff.'
            ], 403);
        }

        $validatedData = $request->validate([
            'first_name' => 'required|string',
            'last_name'  => 'required|string',
            'email'      => 'required|email',
        ]);

        $staff = User::where('email', $validatedData['email'])->first();

        if ($staff) {
            if (in_array($staff->role, [UserRole::ADMIN, UserRole::SUPERADMIN])) {
                return response()->json([
                    'message' => 'This email belongs to an Admin account and cannot be added as staff.'
                ], 422);
            }

            if ($company->staff()->where('users.id', $staff->id)->exists()) {
                return response()->json([
                    'message' => 'This staff member is already part of your company.'
                ], 422);
            }

            $company->staff()->attach($staff->id);

            return response()->json([
                'message' => 'The staff account already exists and has been successfully linked to your company.',
                'staff'   => $staff,
            ], 200);
        }

        $plainPassword = Str::password(8);

        $staff = User::create([
            'first_name' => $validatedData['first_name'],
            'last_name'  => $validatedData['last_name'],
            'email'      => $validatedData['email'],
            'password'   => Hash::make($plainPassword),
            'role'       => UserRole::STAFF,
        ]);

        $company->staff()->attach($staff->id);

        $staff->notify(new StaffAccountCreated($plainPassword));

        return response()->json([
            'message' => 'New staff account created and attached successfully.',
            'staff'   => $staff,
        ], 201);
    }

    // Remove a staff from the company (detach or delete)
    public function removeStaff(Request $request, User $staff)
    {
        $user = $request->user();
        $company = $user->company()->first();

        if ($user->role !== UserRole::ADMIN) {
            return response()->json([
                'message' => 'Unauthorized. Only admins can remove staff.'
            ], 403);
        }

        if (!$company->staff()->where('users.id', $staff->id)->exists()) {
            return response()->json([
                'message' => 'This staff is not part of your company.'
            ], 422);
        }

        // If staff belongs only to this company → delete
        if ($staff->companies()->count() === 1) {
            $staff->delete();
            return response()->json(['message' => 'Staff account deleted successfully.'], 200);
        }

        // Otherwise just detach
        $company->staff()->detach($staff->id);

        return response()->json(['message' => 'Staff removed from your company successfully.'], 200);
    }
}
