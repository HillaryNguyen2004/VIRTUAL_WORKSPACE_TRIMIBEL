<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Optional: restrict to staff or team leaders
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'task_id' => 'required|exists:tasks,id',
            'user_id' => 'required|exists:users,id',
        ];
    }

    public function validatedData(): array
    {
        return $this->only(['task_id', 'user_id']);
    }
}
