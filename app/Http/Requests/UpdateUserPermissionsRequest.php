<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserPermissionsRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Or check: auth()->user()->can('manage permissions')
    }

    public function rules()
    {
        // return [
        //     'user_id' => 'required|exists:users,id',
        //     'permissions' => 'nullable|array',
        //     'permissions.*' => 'exists:permissions,name',
        // ];
        return [
        'role_name' => 'required|string|exists:roles,name',
        'permissions' => 'array',
        'permissions.*' => 'string|exists:permissions,name',
    ];
    }
}
