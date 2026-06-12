<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class AttendanceReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'month' => 'nullable|date_format:Y-m',
            'user_id' => 'nullable|exists:users,id',
        ];
    }

    /**
     * Get validated month as array with year and month
     */
    public function getMonthParts(): array
    {
        $month = $this->input('month', Carbon::now()->format('Y-m'));
        [$year, $monthNum] = explode('-', $month);

        return [
            'year' => intval($year),
            'month' => intval($monthNum),
        ];
    }
}
