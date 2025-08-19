<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getStats(Request $request)
    {
        $user = $request->user();

        // Check if the user is admin
        if ($user && $user->role != UserRole::STAFF) {
            $totalStaff = User::count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_staff' => $totalStaff,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized',
        ], 403);
    }
}
