<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StaffController extends Controller
{
    public function addStaff(Request $request)
    {
        $user = $request->user();

        $validatedData = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => "required|email|string",
        ]);

        $password = Str::password(8);
        $hashedPassword = Hash::make($password);

        $staff = User::create([
            'first_name',
            'last_name',
            'email'
        ]);
    }
}
