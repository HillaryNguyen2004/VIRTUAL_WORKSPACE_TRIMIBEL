<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Holiday;

class StoreHolidayRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Adjust based on your authorization logic
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'start_date' => ['required', 'date', function ($attribute, $value, $fail) {
                $this->validateNoOverlap($value, $this->input('end_date'), null, $fail);
            }],
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'Holiday title is required.',
            'start_date.required' => 'Start date is required.',
            'start_date.date' => 'Start date must be a valid date.',
            'end_date.date' => 'End date must be a valid date.',
            'end_date.after_or_equal' => 'End date must be on or after the start date.',
        ];
    }

    /**
     * Validate that the new holiday doesn't overlap with existing holidays
     */
    private function validateNoOverlap($startDate, $endDate, $excludeId, $fail)
    {
        $endDate = $endDate ?: $startDate; // If no end date, use start date
        
        $overlapping = Holiday::where(function ($query) use ($startDate, $endDate) {
            // Check if new holiday overlaps with existing ones
            $query->where(function ($q) use ($startDate, $endDate) {
                // New holiday starts during an existing holiday
                $q->where('start_date', '<=', $startDate)
                  ->where(function ($q2) use ($startDate) {
                      $q2->where('end_date', '>=', $startDate)
                         ->orWhereNull('end_date');
                  });
            })->orWhere(function ($q) use ($startDate, $endDate) {
                // New holiday ends during an existing holiday
                $q->where('start_date', '<=', $endDate)
                  ->where(function ($q2) use ($endDate) {
                      $q2->where('end_date', '>=', $endDate)
                         ->orWhereNull('end_date');
                  });
            })->orWhere(function ($q) use ($startDate, $endDate) {
                // New holiday completely contains an existing holiday
                $q->where('start_date', '>=', $startDate)
                  ->where('start_date', '<=', $endDate);
            });
        });

        if ($excludeId) {
            $overlapping->where('id', '!=', $excludeId);
        }

        if ($overlapping->exists()) {
            $fail('This holiday overlaps with an existing holiday. Please choose different dates.');
        }
    }
}
