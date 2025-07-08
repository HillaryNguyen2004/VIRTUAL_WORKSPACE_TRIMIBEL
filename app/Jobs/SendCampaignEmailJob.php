<?php
namespace App\Jobs;

use App\Mail\CampaignEmail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;

class SendCampaignEmailJob implements ShouldQueue
{
    // use InteractsWithQueue, Queueable, SerializesModels;
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // protected $user;
    // protected $subject;
    // protected $content;
    public $user;
    public $subject;
    public $content;

    public function __construct(User $user, $subject, $content)
    {
        $this->user = $user;
        $this->subject = $subject;
        $this->content = $content;
    }

    public function handle()
    {
        Mail::to($this->user->email)
            ->send(new CampaignEmail($this->subject, $this->content));
    }
}
