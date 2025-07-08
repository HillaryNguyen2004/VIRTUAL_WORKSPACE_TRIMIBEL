<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Allow all authenticated users, customize if needed
    }

    public function rules(): array
    {
        return [
            'csv_file' => 'required|mimes:csv,txt',
        ];
    }
}
