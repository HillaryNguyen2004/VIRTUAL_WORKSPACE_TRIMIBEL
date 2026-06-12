<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RemoveStaffRequest extends FormRequest
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
            'department_id' => 'required|exists:departments,id',
            'user_id' => 'required|exists:users,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'department_id.required' => 'ID phòng ban là bắt buộc.',
            'department_id.exists' => 'Phòng ban không tồn tại.',
            'user_id.required' => 'ID nhân viên là bắt buộc.',
            'user_id.exists' => 'Nhân viên không tồn tại.',
        ];
    }
}
