<?php
namespace NguyenNguyen\CompanyHour\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyHourRequest extends FormRequest
{
    public function authorize() { return true; }

    public function rules() {
        return [
            'start_at' => 'required|date_format:H:i:s',
            'end_at' => 'required|date_format:H:i:s|after:start_at'
        ];
    }

    protected function prepareForValidation()
{
    $start = $this->input('start_at');
    $end = $this->input('end_at');

    if ($start && strlen($start) === 5) {
        $start .= ':00';
    }

    if ($end && strlen($end) === 5) {
        $end .= ':00';
    }

    $this->merge([
        'start_at' => $start,
        'end_at'   => $end,
    ]);
}

}
