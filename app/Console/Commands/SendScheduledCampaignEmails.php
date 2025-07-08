<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Campaign;
use App\Jobs\SendCampaignEmailJob;
use Illuminate\Support\Carbon;

class SendScheduledCampaignEmails extends Command
{
    protected $signature = 'campaigns:send-scheduled';
    protected $description = 'Send scheduled campaign emails';

    public function handle()
    {
        $campaigns = Campaign::whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->where('sent', false)
            ->with('users')
            ->get();

        foreach ($campaigns as $campaign) {
            foreach ($campaign->users as $user) {
                $replacements = [
                    '{first_name}' => $user->name,
                    '{birthday}' => $user->birthday,
                    '{email}' => $user->email,
                    '{site_title}' => config('app.name'),
                ];

                $subject = strtr($campaign->subject, $replacements);
                $content = strtr($campaign->content, $replacements);

                SendCampaignEmailJob::dispatch($user, $subject, $content);
            }

            $campaign->sent = true;
            $campaign->save();
            $this->info("Sent campaign: {$campaign->name}");
        }
    }
}

