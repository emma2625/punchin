<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // anyone can register
    }

    public function rules(): array
    {
        return [
            // Company rules
            'company.name' => 'required|string|min:2|max:255',
            'company.email' => 'required|email|unique:companies,email',
            'company.phone' => 'required|string|min:7|max:20',

            // User rules
            'user.avatar' => 'nullable|image|max:5120',
            'user.first_name' => 'required|string|min:2|max:50',
            'user.last_name' => 'required|string|min:2|max:50',
            'user.email' => 'required|email|unique:users,email',
            'user.password' => 'required|string|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'company.name.required' => 'Company name is required',
            'company.email.required' => 'Company email is required',
            'company.email.email' => 'Company email must be a valid email address',
            'company.email.unique' => 'This company email is already taken',
            'company.phone.required' => 'Company phone is required',

            'user.first_name.required' => 'First name is required',
            'user.last_name.required' => 'Last name is required',
            'user.email.required' => 'User email is required',
            'user.email.email' => 'User email must be a valid email address',
            'user.email.unique' => 'This email is already taken',
            'user.password.required' => 'Password is required',
            'user.password.confirmed' => 'Password confirmation does not match',
            'user.avatar.max' => 'Avatar must not be greater than 5mb',
        ];
    }
}
