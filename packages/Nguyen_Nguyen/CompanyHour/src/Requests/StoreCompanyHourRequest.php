<?php
namespace NguyenNguyen\CompanyHour\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyHourRequest extends FormRequest
{
    public function authorize() { return true; }

    public function rules() {
        return [
            'start_at' => 'required|date_format:H:i',
            'end_at' => 'required|date_format:H:i|after:start_at'
        ];
    }
}
