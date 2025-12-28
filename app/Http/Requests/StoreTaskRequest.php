<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;


class StoreTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    // public function authorize(): bool
    // {
    //     return true; // You can replace this with role check if needed
    // }

    /**
     * Get the validation rules that apply to the request.
     */
    // public function rules(): array
    // {
    //     return [
    //         'title' => 'required|string|max:255',
    //         'assignee' => 'required|exists:users,id',
    //         'due_date' => 'required|date|after_or_equal:today',
    //         'description' => 'nullable|string',
    //         'active' => 'nullable|boolean',
    //     ];
    // }

    public function rules(): array
    {
        return [
            'tasks' => 'required|array|min:1',
            'tasks.*.title' => 'required|string|max:255',
            'tasks.*.project_id' => 'required|exists:projects,id',
            'tasks.*.assignee' => 'required|exists:users,id',
            'tasks.*.start_date' => 'required|date',
            'tasks.*.due_date' => 'required|date|after_or_equal:today',
            'tasks.*.description' => 'nullable|string',
            'tasks.*.active' => 'nullable|boolean',
        ];
    }




    /**
     * Prepare and format the validated data for storage.
     */
    public function formatted(): array
    {
        $data = $this->validated();

        return [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'assigned_user_id' => $data['assignee'],
            'due_date' => $data['due_date'],
            'status' => 'pending',
            'active' => $this->has('active') ? 1 : 0,
        ];
    }

    public function messages(): array
    {
        return [
            'due_date.after_or_equal' => 'The due date must be today or a future date.',
        ];
    }

    public function authorize(): bool
    {
        $user = auth()->user();

        // Admin can assign anyone
        if ($user->hasRole('admin')) {
            return true;
        }

        // Staff: can only assign their team members
        $teamUserIds = User::where('team_leader_id', $user->id)->pluck('id')->toArray();

        return collect($this->assignees)->every(
            fn ($id) => in_array($id, $teamUserIds)
        );
    }

}
