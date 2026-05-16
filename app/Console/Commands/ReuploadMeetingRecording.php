<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Models\MeetingHistory;

class ReuploadMeetingRecording extends Command
{
    protected $signature = 'meeting:reupload-recording {meetingId}';
    protected $description = 'Re-upload the composed recording for a meeting to S3';

    public function handle()
    {
        $meetingId = $this->argument('meetingId');
        $domain = config('services.metered.domain');
        $secretKey = config('services.metered.secret_key');

        $this->info("Fetching recordings for meeting: {$meetingId}");

        $response = Http::get("https://{$domain}/api/v1/recordings/room/{$meetingId}", [
            'secretKey' => $secretKey,
        ]);

        $recordingsData = $response->json('data') ?? [];

        if (empty($recordingsData)) {
            $this->error('No recordings found from Metered API.');
            return 1;
        }

        $composed = collect($recordingsData)->first(
            fn($r) => ($r['type'] ?? '') === 'video+audio' && ($r['participantUsername'] ?? '') === 'composer'
        );

        if (!$composed) {
            $this->error('No composed recording found in Metered data.');
            return 1;
        }

        $recordingId = $composed['_id'];
        $this->info("Found composed recording: {$recordingId}");

        $linkResponse = Http::get("https://{$domain}/api/v1/recording/{$recordingId}/download", [
            'secretKey' => $secretKey,
        ]);

        $s3Url = $linkResponse->json('url');
        if (!$s3Url) {
            $this->error('Failed to get download URL from Metered.');
            return 1;
        }

        $localPath = storage_path("app/recordings/meeting_{$meetingId}_full.mp4");
        @mkdir(dirname($localPath), 0755, true);

        $this->info('Downloading from Metered...');
        Http::withOptions(['sink' => $localPath])->timeout(600)->get($s3Url);

        if (!file_exists($localPath) || filesize($localPath) === 0) {
            $this->error('Download failed or file is empty.');
            @unlink($localPath);
            return 1;
        }

        $this->info('Uploading to S3...');
        $s3Key = "video-meeting/{$meetingId}/full_recording.mp4";
        $uploaded = Storage::disk('s3')->put($s3Key, fopen($localPath, 'r'));

        @unlink($localPath);

        if (!$uploaded) {
            $this->error('S3 upload failed. Check your S3 credentials and bucket permissions.');
            return 1;
        }

        MeetingHistory::where('meeting_id', $meetingId)
            ->update(['recording_url' => $s3Key]);

        $this->info("Done. Recording uploaded to: {$s3Key}");
        return 0;
    }
}
