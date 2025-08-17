<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Branch;
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
            return response()->json(['message' => 'Unauthorized. Only admins can view staff.'], 403);
        }

        $company = $user->company()->first();
        if (!$company) {
            return response()->json(['message' => 'You do not belong to any company.'], 404);
        }

        $staff = $company->staff()->with('branches')->latest()->paginate(20);

        return UserResource::collection($staff)->response();
    }

    // Add a new staff or attach existing staff (branch via ULID)
    public function addStaff(Request $request)
    {
        $user = $request->user();

        if ($user->role !== UserRole::ADMIN) {
            return response()->json(['message' => 'Unauthorized. Only admins can add staff.'], 403);
        }

        $company = $user->company()->first();
        if (!$company || !$company->activeSubscription()->exists()) {
            return response()->json(['message' => 'You need an active subscription to add staff.'], 403);
        }

        $validated = $request->validate([
            'first_name' => 'required|string',
            'last_name'  => 'required|string',
            'email'      => 'required|email',
            'branch_ulid' => 'nullable|string', // branch ULID
        ]);

        $staff = User::where('email', $validated['email'])->first();

        if ($staff) {
            if (in_array($staff->role, [UserRole::ADMIN, UserRole::SUPERADMIN])) {
                return response()->json(['message' => 'This email belongs to an Admin account and cannot be added as staff.'], 422);
            }

            if ($company->staff()->where('users.id', $staff->id)->exists()) {
                return response()->json(['message' => 'This staff member is already part of your company.'], 422);
            }

            $company->staff()->attach($staff->id);

            // Attach branch if ULID provided
            if (!empty($validated['branch_ulid'])) {
                $branch = $company->branches()->where('ulid', $validated['branch_ulid'])->first();
                $branch?->staff()->attach($staff->id);
            }

            return response()->json([
                'message' => 'The staff account already exists and has been successfully linked to your company.',
                'staff' => new UserResource($staff),
            ], 200);
        }

        // Create new staff
        $plainPassword = Str::password(8);

        $staff = User::create([
            'first_name' => $validated['first_name'],
            'last_name'  => $validated['last_name'],
            'email'      => $validated['email'],
            'password'   => Hash::make($plainPassword),
            'role'       => UserRole::STAFF,
        ]);

        $company->staff()->attach($staff->id);

        if (!empty($validated['branch_ulid'])) {
            $branch = $company->branches()->where('ulid', $validated['branch_ulid'])->first();
            $branch?->staff()->attach($staff->id);
        }

        $staff->notify(new StaffAccountCreated($plainPassword));

        return response()->json([
            'message' => 'New staff account created and attached successfully.',
            'staff'   => new UserResource($staff),
        ], 201);
    }

    // Update staff branch via ULID
    public function updateStaffBranch(Request $request, User $staff)
    {
        $user = $request->user();
        $company = $user->company()->first();

        if ($user->role !== UserRole::ADMIN) {
            return response()->json(['message' => 'Unauthorized. Only admins can update staff.'], 403);
        }

        if (!$company->staff()->where('users.id', $staff->id)->exists()) {
            return response()->json(['message' => 'This staff is not part of your company.'], 422);
        }

        $validated = $request->validate([
            'branch_ulid' => 'nullable|string',
        ]);

        // Detach from all branches in this company first
        $staff->branches()->where('company_id', $company->id)->detach();

        // Attach to new branch if provided
        if (!empty($validated['branch_ulid'])) {
            $branch = $company->branches()->where('ulid', $validated['branch_ulid'])->first();
            $branch?->staff()->attach($staff->id);
        }

        return response()->json([
            'message' => 'Staff branch updated successfully.',
            'staff' => new UserResource($staff->fresh('branches')),
        ], 200);
    }

    // Remove a staff from the company
    public function removeStaff(Request $request, $staff)
    {
        $user = $request->user();
        $company = $user->company()->first();

        $staff = User::fromUlid($staff);

        if ($user->role !== UserRole::ADMIN) {
            return response()->json(['message' => 'Unauthorized. Only admins can remove staff.'], 403);
        }

        if (!$company->staff()->where('users.id', $staff->id)->exists()) {
            return response()->json(['message' => 'This staff is not part of your company.'], 422);
        }

        // If staff belongs only to this company → delete
        if ($staff->companies()->count() === 1) {
            $staff->forceDelete();
            return response()->json(['message' => 'Staff account deleted successfully.'], 200);
        }

        // Otherwise just detach
        $company->staff()->detach($staff->id);

        return response()->json(['message' => 'Staff removed from your company successfully.'], 200);
    }
}
