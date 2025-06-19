<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StaffTaskFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Optionally restrict to staff role
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:pending,in_progress,completed',
        ];
    }

    public function filters(): array
    {
        return $this->only(['search', 'status']);
    }
}
