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

        $user->load('company');

        return UserResource::make($user);
    }
}
