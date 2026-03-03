<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppSettings extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_settings';

    protected $fillable = [
        'business_name',
        'waba_id',
        'phone_number_id',
        'phone_number',
        'access_token',
        'verify_token',
        'webhook_url',
        'is_active',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    protected $hidden = ['access_token', 'verify_token'];

    /**
     * Get the active WhatsApp settings
     */
    public static function active()
    {
        return self::where('is_active', true)->first();
    }

    /**
     * Get WhatsApp API URL
     */
    public function getApiUrl(string $path = ''): string
    {
        $basePath = "https://graph.instagram.com/v18.0/{$this->phone_number_id}";
        return $basePath . ($path ? '/' . ltrim($path, '/') : '');
    }

    /**
     * Get API headers for requests
     */
    public function getApiHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->access_token,
            'Content-Type' => 'application/json'
        ];
    }
}
