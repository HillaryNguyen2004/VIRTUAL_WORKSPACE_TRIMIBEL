<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // restrict to admin via policy/gate
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'role' => 'required|in:user,staff,admin',
            'team_members' => 'nullable|array',
            'team_members.*' => 'nullable|exists:users,id',
        ];
    }
}
