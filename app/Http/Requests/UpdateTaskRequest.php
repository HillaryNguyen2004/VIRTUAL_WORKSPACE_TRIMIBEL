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
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $user = auth()->user();

        $rules = [
            'title' => 'required|string|max:255',
            'project_id' => 'required|exists:projects,id',
            'phase_id' => 'nullable|exists:phases,id',
            'start_date' => 'required|date',
            'due_date' => 'required|date',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed',
            'priority' => 'required|in:low,normal,high,critical',
            'percentage' => 'required|integer|min:0|max:100',
            'estimated_time' => 'numeric|min:1',
            'active' => 'boolean',
            'score' => 'nullable|integer|min:0|max:100',
        ];

        // If user is admin, assignee is required
        if ($user->hasRole('admin')) {
            $rules['assignee'] = 'required|exists:users,id';
        } else {
            // For staff, assignee is optional? But probably required
            $rules['assignee'] = 'required|exists:users,id';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'assignees.required' => 'At least one assignee is required.',
            'assignees.min' => 'Please select at least one assignee.',
        ];
    }
}