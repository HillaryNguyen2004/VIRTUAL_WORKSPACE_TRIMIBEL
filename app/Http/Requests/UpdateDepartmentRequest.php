<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDepartmentRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:departments,name,' . $this->department->id,
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên phòng ban là bắt buộc.',
            'name.max' => 'Tên phòng ban không được vượt quá 255 ký tự.',
            'name.unique' => 'Tên phòng ban này đã tồn tại.',
        ];
    }
}
