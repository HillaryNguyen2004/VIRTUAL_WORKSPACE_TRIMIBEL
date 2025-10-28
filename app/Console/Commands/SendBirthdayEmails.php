<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Http\Controllers\CampaignController;

class SendBirthdayEmails extends Command
{
    protected $signature = 'emails:birthday';
    protected $description = 'Send birthday emails to users with birthday today';

    public function handle()
    {
        app(CampaignController::class)->sendBirthdayEmails();
        $this->info('Birthday emails sent.');
    }
}

