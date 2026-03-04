<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OpenBoardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'board_id' => 'required|string|min:36|max:36'
        ];
    }
}
