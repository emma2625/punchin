<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function profile(Request $request)
    {
        $user = $request->user();

        // Ensure relationship exists in the model  
        $user->load([
            'company',
            'company.activeSubscription',
        ]);

        return new UserResource($user);
    }
}
