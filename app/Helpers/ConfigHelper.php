<?php

namespace App\Helpers;

class ConfigHelper
{
    /**
     * Get allowed domains for the current environment
     */
    public static function getAllowedDomains(): array
    {
        $domains = [];
        
        // Always include current app URL
        if ($appUrl = config('app.url')) {
            $parsed = parse_url($appUrl);
            $domain = $parsed['host'];
            if (isset($parsed['port'])) {
                $domain .= ':' . $parsed['port'];
            }
            $domains[] = $domain;
        }
        
        // Add environment-specific domains
        if (app()->environment('local')) {
            $domains = array_merge($domains, [
                'localhost',
                'localhost:8000',
                'localhost:3000',
                '127.0.0.1',
                '127.0.0.1:8000',
                '::1',
                'laravel.test'
            ]);
        }
        
        // Add custom domains from env
        if ($customDomains = env('SANCTUM_STATEFUL_DOMAINS')) {
            $domains = array_merge($domains, explode(',', $customDomains));
        }
        
        return array_unique(array_filter($domains));
    }
    
    /**
     * Check if the current environment supports any port
     */
    public static function supportsAnyPort(): bool
    {
        return app()->environment(['local', 'development', 'testing']);
    }
}