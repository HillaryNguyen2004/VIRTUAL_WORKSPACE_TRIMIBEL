<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

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
            'half_day_period' => ['required_if:leave_type,OFF_HALF', 'nullable', 'in:AM,PM'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $startDate = $this->input('start_date');
            $endDate = $this->input('end_date');

            if ($startDate && $endDate) {
                $conflictingHolidays = $this->checkHolidayConflicts($startDate, $endDate);
                
                if (!empty($conflictingHolidays)) {
                    $holidayTitles = implode(', ', array_map(function($holiday) {
                        return $holiday['title'] . ' (' . Carbon::parse($holiday['date'])->format('M d, Y') . ')';
                    }, $conflictingHolidays));
                    
                    $validator->errors()->add(
                        'start_date', 
                        'Cannot request day-off on holiday dates: ' . $holidayTitles
                    );
                }
            }
        });
    }

    /**
     * Check if requested dates conflict with any holidays
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    private function checkHolidayConflicts($startDate, $endDate)
    {
        $requestedPeriod = CarbonPeriod::create($startDate, $endDate);
        $conflictingHolidays = [];

        // Get all holidays
        $holidays = Holiday::all();

        foreach ($requestedPeriod as $date) {
            foreach ($holidays as $holiday) {
                $holidayStart = Carbon::parse($holiday->start_date)->startOfDay();
                $holidayEnd = Carbon::parse($holiday->end_date)->endOfDay();
                
                if ($date->between($holidayStart, $holidayEnd)) {
                    $conflictingHolidays[] = [
                        'title' => $holiday->title,
                        'date' => $date->toDateString()
                    ];
                    break; // Don't add the same day multiple times
                }
            }
        }

        return $conflictingHolidays;
    }
}