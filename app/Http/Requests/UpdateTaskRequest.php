<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // You can change this to use role-based checks (e.g., auth()->user()->hasRole('admin'))
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
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

    /**
     * Format validated data for repository update
     */
    public function formatted(): array
    {
        $data = $this->validated();

        return [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'assigned_user_id' => $data['assignee'],
            'due_date' => $data['due_date'],
            'status' => $data['status'],
            'active' => $this->has('active') ? 1 : 0,
        ];
    }
}
