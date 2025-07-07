<?php

namespace App\Services;

use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Mail;

class EmailTemplateService
{
    public function sendTemplateEmail(int $templateId, string $recipientEmail, array $data): void
    {
        $template = EmailTemplate::findOrFail($templateId);

        $subject = strtr($template->subject, $data);
        $content = strtr($template->content, $data);

        Mail::send([], [], function ($message) use ($recipientEmail, $subject, $content) {
            $message->to($recipientEmail)
                    ->subject($subject)
                    ->setBody($content, 'text/html');
        });
    }
}
