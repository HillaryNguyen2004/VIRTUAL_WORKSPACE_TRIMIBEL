<?php

use Illuminate\Support\Facades\File;


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
