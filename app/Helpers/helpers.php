<?php

use App\Helpers\FormatHelper;
use Illuminate\Support\Facades\Storage;


if (!function_exists('getUserAvatar')) {
    function getUserAvatar($user)
    {
        // If $user is a string, just return the default
        if (is_string($user) || is_null($user)) {
            return asset('img/undraw_profile_2.svg');
        }

        // If $user is an object and has user_profile_photo
        if (!empty($user->user_profile_photo)) {
            $photo = (string) $user->user_profile_photo;
            if (str_starts_with($photo, 'http')) {
                return $photo;
            }

            $legacyPath = public_path('img/user_avatar/' . $photo);
            if (file_exists($legacyPath)) {
                return asset('img/user_avatar/' . $photo);
            }

            $url = storageUrl($photo);
            if ($url) {
                return $url;
            }
        }

        return asset('img/undraw_profile_2.svg');
    }
}

if (!function_exists('storageUrl')) {
    function storageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        $disk = Storage::disk();
        if ($disk->exists($path)) {
            return $disk->url($path);
        }

        return asset('storage/' . ltrim($path, '/'));
    }
}

if (!function_exists('formatBytes')) {
    /**
     * Format bytes to human-readable format (B, KB, MB, GB)
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    function formatBytes($bytes, $precision = 2)
    {
        return FormatHelper::formatBytes($bytes, $precision);
    }
}
