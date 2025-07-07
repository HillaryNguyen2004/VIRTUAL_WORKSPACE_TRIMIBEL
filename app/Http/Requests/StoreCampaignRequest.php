<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    public function authorize()
    {
        return true; // allow all users (adjust if needed)
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'subject' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'scheduled_at' => 'nullable|date|after_or_equal:today',
            'users' => 'nullable|array',
            'users.*' => 'exists:users,id',
            'email_template_id' => 'nullable|exists:email_templates,id',
        ];
    }
}
