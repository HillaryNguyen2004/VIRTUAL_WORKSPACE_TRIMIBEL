<?php
namespace NguyenNguyen\CompanyHour\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyHourRequest extends FormRequest
{
    public function authorize() { return true; }

    public function rules() {
        return [
            'start_at' => 'required|date_format:H:i:s',
            'end_at'   => 'required|date_format:H:i:s|after:start_at',
            
            // Lunch fields: Required ONLY if checkbox is checked
            'lunch_start' => 'nullable|required_with:has_lunch_break|date_format:H:i:s|after:start_at|before:end_at',
            'lunch_end'   => 'nullable|required_with:has_lunch_break|date_format:H:i:s|after:lunch_start|before:end_at',
            
            // Mid-day: Required ONLY if checkbox is UNCHECKED
            'mid_day'  => 'nullable|required_without:has_lunch_break|date_format:H:i:s|after:start_at|before:end_at',
            
            // Working days: At least one day must be selected
            'working_days' => 'nullable|array|min:1',
            'working_days.*' => 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday'
        ];
    }

    protected function prepareForValidation()
    {
        // 1. Helper to format time strings (add :00 if needed)
        $formatTime = function ($time) {
            return ($time && strlen($time) === 5) ? $time . ':00' : $time;
        };

        // 2. Capture inputs (and handle the naming mismatch fix)
        // We look for 'mid_day' first, fallback to 'mid_day'
        $midDayInput = $this->input('mid_day');
        
        $data = [
            'start_at' => $formatTime($this->input('start_at')),
            'end_at'   => $formatTime($this->input('end_at')),
        ];

        // 3. Logic: "Lunch Break" vs "Mid-day"
        if ($this->has('has_lunch_break')) {
            // Case A: User checked "Lunch break?" (YES)
            // We validate lunch fields, but force mid_day to be NULL
            $data['lunch_start'] = $formatTime($this->input('lunch_start'));
            $data['lunch_end']   = $formatTime($this->input('lunch_end'));
            $data['mid_day']  = null; 
        } else {
            // Case B: User unchecked "Lunch break?" (NO)
            // We validate mid-day, but force lunch fields to be NULL
            $data['lunch_start'] = null;
            $data['lunch_end']   = null;
            $data['mid_day']  = $formatTime($midDayInput); // Use the captured input
        }

        // 4. Handle working_days - convert from checkbox array to validated array
        // Checkboxes send only selected values, so we get an array directly
        $workingDays = $this->input('working_days');
        if (is_array($workingDays)) {
            // Filter out any empty values and keep only valid days
            $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $data['working_days'] = array_filter($workingDays, function ($day) use ($validDays) {
                return in_array($day, $validDays);
            });
            // If no days selected, set to null to trigger validation error
            if (empty($data['working_days'])) {
                $data['working_days'] = null;
            }
        } else {
            $data['working_days'] = null;
        }

        // 5. Merge sanitized data back into request for validation
        // This ensures the validator sees 'mid_day' even if the form sent 'mid_day'
        $this->merge($data);
    }
}