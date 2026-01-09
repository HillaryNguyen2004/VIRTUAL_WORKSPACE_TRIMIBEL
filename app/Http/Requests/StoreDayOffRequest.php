<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDayOffRequest extends FormRequest
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
            'start_date' => ['required', 'date', 'after:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'leave_type' => ['required', 'in:OFF_FULL,OFF_HALF'],
            'reason' => ['nullable', 'string'],
        ];
    }
}