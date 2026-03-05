<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'staff_id' => 'required|exists:users,id',
            'status' => 'nullable|in:active,inactive',
            'start_date' => 'required|date',
            'due_date' => 'required|date'
        ];
    }
}
