<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppMessageTemplate extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_message_templates';

    protected $fillable = [
        'template_name',
        'language',
        'category',
        'body',
        'example',
        'variables',
        'status',
        'rejection_reason',
        'meta_template_id',
        'quality_rating',
        'created_by_user_id',
        'is_active'
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved')->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByLanguage($query, $language)
    {
        return $query->where('language', $language);
    }

    /**
     * Replace variables in template with actual values
     */
    public function renderWithVariables(array $values): string
    {
        $text = $this->body;

        if (!empty($this->variables)) {
            foreach ($this->variables as $index => $variable) {
                $placeholder = '{{' . ($index + 1) . '}}';
                $value = $values[$variable] ?? '';
                $text = str_replace($placeholder, $value, $text);
            }
        }

        return $text;
    }

    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
        };
    }
}
