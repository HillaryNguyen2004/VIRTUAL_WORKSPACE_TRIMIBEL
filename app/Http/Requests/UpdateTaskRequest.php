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
            'start_date' => 'required|date',
            'due_date' => 'required|date',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed',
            'active' => 'boolean',
        ];
        
        // If user is admin, assignees are required
        if ($user->hasRole('admin')) {
            $rules['assignees'] = 'required|array|min:1';
            $rules['assignees.*'] = 'exists:users,id';
        } else {
            // For staff, assignees are optional
            $rules['assignees'] = 'nullable|array';
            $rules['assignees.*'] = 'exists:users,id';
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