<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Optionally, you can restrict to admin users
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'roles' => 'required|in:user,staff',
            'department_id' => 'required|exists:departments,id',
            'team_leader_id' => 'nullable|exists:users,id',
        ];
    }

    public function validatedData(): array
    {
        return $this->only(['name', 'email', 'roles', 'department_id', 'team_leader_id']);
    }
}
