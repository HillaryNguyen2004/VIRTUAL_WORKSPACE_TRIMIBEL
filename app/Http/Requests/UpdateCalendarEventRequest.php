<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCalendarEventRequest extends FormRequest
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
        return [
            'id' => 'required|string',
            'title' => 'required|string|max:255',
            'category' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'meeting_id' => 'nullable|string',
            'recurrence_type' => 'nullable|string',
            'recurrence_interval' => 'nullable|integer|min:1',
            'recurrence_end_date' => 'nullable|date',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'id.required' => 'Event ID is required',
            'title.required' => 'Event title is required',
            'start_date.required' => 'Start date is required',
        ];
    }
}
