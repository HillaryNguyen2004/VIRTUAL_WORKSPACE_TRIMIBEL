<?php
namespace App\Services;
use Carbon\Carbon;
use App\Models\User;
use App\Models\EmailTemplate;
use App\Jobs\SendCampaignEmailJob;

class BirthdayEmailService
{
    // public function send()
    // {
    //     $users = User::whereMonth('birthday', now()->month)
    //         ->whereDay('birthday', now()->day)
    //         ->get();

    //     if ($users->isEmpty()) return;

    //     $template = EmailTemplate::where('name', 'Birthday')->first();

    //     if (!$template) return;

    //     foreach ($users as $user) {
    //         $replacements = [
    //             '{first_name}' => $user->name,
    //             '{birthday}' => $user->birthday,
    //             '{email}' => $user->email,
    //             '{site_title}' => config('app.name'),
    //         ];

    //         $subject = strtr($template->subject, $replacements);
    //         $content = strtr($template->content, $replacements);

    //         dispatch(new SendCampaignEmailJob($user, $subject, $content));
    //     }
    // }


    public function send()
    {
        $today = now()->toDateString();

        $users = User::whereMonth('birthday', now()->month)
            ->whereDay('birthday', now()->day)
            ->where(function ($query) use ($today) {
                $query->whereNull('birthday_email_sent_at')
                      ->orWhereDate('birthday_email_sent_at', '!=', $today);
            })
            ->get();

        if ($users->isEmpty()) return;

        $template = EmailTemplate::where('name', 'Birthday')->first();

        if (!$template) return;

        foreach ($users as $user) {
            $replacements = [
                '{first_name}' => $user->name,
                '{birthday}' => $user->birthday,
                '{email}' => $user->email,
                '{site_title}' => config('app.name'),
            ];

            $subject = strtr($template->subject, $replacements);
            $content = strtr($template->content, $replacements);

            dispatch(new SendCampaignEmailJob($user, $subject, $content));

            // ✅ Mark email as sent today
            $user->birthday_email_sent_at = Carbon::now();
            $user->save();
        }
    }
}
