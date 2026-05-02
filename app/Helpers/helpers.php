<?php

use Illuminate\Support\Facades\File;
use App\Helpers\FormatHelper;


if (!function_exists('getUserAvatar')) {
    function getUserAvatar($user)
    {
        // If $user is a string, just return the default
        if (is_string($user) || is_null($user)) {
            return asset('img/undraw_profile_2.svg');
        }

        // If $user is an object and has user_profile_photo
        if (!empty($user->user_profile_photo)) {
            $avatarPath = public_path('img/user_avatar/' . $user->user_profile_photo);
            if (file_exists($avatarPath)) {
                return asset('img/user_avatar/' . $user->user_profile_photo);
            }
        }

        return asset('img/undraw_profile_2.svg');
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
