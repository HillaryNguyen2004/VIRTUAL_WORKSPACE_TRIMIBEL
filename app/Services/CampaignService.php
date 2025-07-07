<?php
namespace App\Services;

use App\Models\Campaign;
use App\Models\User;
use App\Models\EmailTemplate;
use App\Jobs\SendCampaignEmailJob;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CampaignService
{
    public function createCampaign(array $data)
    {
        $template = EmailTemplate::find($data['email_template_id'] ?? null);

        $subject = $template?->subject ?? $data['subject'];
        $content = $template?->content ?? $data['content'];

        $scheduledAt = $data['scheduled_at']
            ? Carbon::parse($data['scheduled_at'], 'Asia/Ho_Chi_Minh')->setTimezone('UTC')
            : null;

        $campaign = Campaign::create([
            'name' => $data['name'],
            'subject' => $subject,
            'content' => $content,
            'scheduled_at' => $scheduledAt,
            'email_template_id' => $data['email_template_id'] ?? null,
        ]);

        // Attach users
        $users = collect();
        if (!empty($data['send_to_all'])) {
            $users = User::role('user')->get();
        } elseif (!empty($data['users'])) {
            $users = User::whereIn('id', $data['users'])->get();
        }

        if ($users->isNotEmpty()) {
            $campaign->users()->attach($users->pluck('id'));
        }

        return $campaign;
    }

    public function updateCampaign(Campaign $campaign, array $data)
    {
        $newSchedule = $data['scheduled_at']
            ? Carbon::parse($data['scheduled_at'], 'Asia/Ho_Chi_Minh')->setTimezone('UTC')
            : null;

        $originalSchedule = $campaign->scheduled_at?->format('Y-m-d H:i');
        $newScheduleStr = $newSchedule?->format('Y-m-d H:i');

        $shouldReset = $originalSchedule !== $newScheduleStr;

        $campaign->update([
            'name' => $data['name'],
            'subject' => $data['subject'] ?? $campaign->subject,
            'content' => $data['content'] ?? $campaign->content,
            'scheduled_at' => $newSchedule,
            'sent' => $shouldReset ? false : $campaign->sent,
        ]);

        $campaign->users()->sync($data['users'] ?? []);
    }

    public function sendNow(Campaign $campaign): bool|string
    {
        if ($campaign->sent) return false;

        $subject = $campaign->subject;
        $content = $campaign->content;

        if ((!$subject || !$content) && $campaign->email_template_id) {
            $template = EmailTemplate::find($campaign->email_template_id);
            if (!$template) return 'template_missing';
            $subject = $subject ?: $template->subject;
            $content = $content ?: $template->content;
        }

        foreach ($campaign->users as $user) {
            $replacements = [
                '{first_name}' => $user->name,
                '{birthday}' => $user->birthday,
                '{email}' => $user->email,
                '{site_title}' => config('app.name'),
            ];

            $finalSubject = strtr($subject, $replacements);
            $finalContent = strtr($content, $replacements);

            dispatch(new SendCampaignEmailJob($user, $finalSubject, $finalContent));
        }

        $campaign->sent = true;
        $campaign->save();

        return true;
    }

    public function processDueCampaigns()
    {
        $dueCampaigns = Campaign::whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->where('sent', false)
            ->with('users')
            ->get();

        foreach ($dueCampaigns as $campaign) {
            $this->sendNow($campaign);
        }
    }
}
