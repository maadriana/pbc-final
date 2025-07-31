<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\User;

class StoreUserRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user()->canCreateUsers();
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => [
                'required',
                Rule::in([
                    User::ROLE_SYSTEM_ADMIN,
                    User::ROLE_ENGAGEMENT_PARTNER,
                    User::ROLE_MANAGER,
                    User::ROLE_ASSOCIATE,
                    User::ROLE_CLIENT
                ])
            ],
        ];
    }

    public function messages()
    {
        return [
            'role.required' => 'Please select a role.',
            'role.in' => 'The selected role is invalid. Please choose from: System Admin, Engagement Partner, Manager, Associate, or Client.',
        ];
    }
}
