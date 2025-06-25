<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Optional: add admin/staff check here
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'role' => 'nullable|string|exists:roles,name',
            'sort' => 'in:asc,desc' ,
        ];
    }

    public function filters(): array
    {
        return $this->only(['search', 'role','sort']);
    }
}
