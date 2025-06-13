<?php

use Illuminate\Support\Facades\File;

if (!function_exists('getUserAvatar')) {
    function getUserAvatar($email)
    {
        $safeEmail = str_replace(['@', '.'], '_', $email);
        $jpgPath = 'img/user_avatar/' . $safeEmail . '.jpg';
        $pngPath = 'img/user_avatar/' . $safeEmail . '.png';
        $jpegPath = 'img/user_avatar/' . $safeEmail . '.jpeg';

        if (File::exists(public_path($jpgPath))) {
            return asset($jpgPath);
        } elseif (File::exists(public_path($pngPath))) {
            return asset($pngPath);
        }
        elseif (File::exists(public_path($jpegPath))) {
            return asset($jpegPath);
        }
        return asset('img/undraw_profile_2.svg');
    }
}
