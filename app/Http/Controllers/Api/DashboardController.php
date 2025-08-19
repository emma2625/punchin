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

        if ($user && $user->role === UserRole::ADMIN) {
            $totalStaff = $user->company->staff()->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'staff' => [
                        'label' => 'Total Staff',
                        'value' => $totalStaff,
                        'icon' => 'people',
                        'color' => '#3B82F6',
                        'change' => '+2',
                        'changeType' => 'increase',
                    ],
                    'presentToday' => [
                        'label' => 'Present Today',
                        'value' => 18,
                        'icon' => 'check-circle',
                        'color' => '#4CAF50',
                        'change' => '+5',
                        'changeType' => 'increase',
                    ],
                    'lateArrivals' => [
                        'label' => 'Late Arrivals',
                        'value' => 3,
                        'icon' => 'schedule',
                        'color' => '#FF9800',
                        'change' => '-1',
                        'changeType' => 'decrease',
                    ],
                    'absent' => [
                        'label' => 'Absent',
                        'value' => 3,
                        'icon' => 'cancel',
                        'color' => '#F44336',
                        'change' => '0',
                        'changeType' => 'neutral',
                    ],
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized',
        ], 403);
    }
}
