<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
        'title' => 'required|string|max:255',
        'assignee' => 'required|exists:users,id',
        'due_date' => 'required|date',
        'description' => 'nullable|string',
        'active' => 'nullable|boolean',
        'status' => 'required|in:pending,in_progress,completed',
    ];
    }
}
