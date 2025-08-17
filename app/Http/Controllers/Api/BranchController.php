<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Enums\UserRole;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    // List all branches for the admin's company
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role !== UserRole::ADMIN) {
            return response()->json(['message' => 'Unauthorized. Only admins can view branches.'], 403);
        }

        $company = $user->company()->first();
        if (!$company) {
            return response()->json(['message' => 'You do not belong to any company.'], 404);
        }

        $branches = $company->branches()->with('staff')->latest()->get();

        return BranchResource::collection($branches);
    }

    // Add a new branch
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== UserRole::ADMIN) {
            return response()->json(['message' => 'Unauthorized. Only admins can add branches.'], 403);
        }

        $company = $user->company()->first();
        if (!$company) {
            return response()->json(['message' => 'You do not belong to any company.'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        $branch = $company->branches()->create($validated);

        return response()->json([
            'message' => 'Branch created successfully.',
            'branch' => new BranchResource($branch),
        ], 201);
    }

    // Update a branch
    public function update(Request $request, Branch $branch)
    {
        $user = $request->user();

        if ($user->role !== UserRole::ADMIN || $branch->company_id !== $user->company->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        $branch->update($validated);

        return response()->json([
            'message' => 'Branch updated successfully.',
            'branch' => new BranchResource($branch),
        ]);
    }

    // Delete a branch
    public function destroy(Request $request, Branch $branch)
    {
        $user = $request->user();

        if ($user->role !== UserRole::ADMIN || $branch->company_id !== $user->company->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $branch->delete();

        return response()->json(['message' => 'Branch deleted successfully.']);
    }
}
