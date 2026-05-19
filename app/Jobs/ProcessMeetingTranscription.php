<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Google\Cloud\Storage\StorageClient;
use App\Models\MeetingTranscription;
use App\Models\MeetingHistory;

class ProcessMeetingTranscription implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // 1. Allow the job to be released/attempted up to 10 times
    public $tries = 10; 

    // 2. Give FFmpeg and Google STT up to 10 minutes (600 seconds) to process the audio
    public $timeout = 600;

    protected $meetingId;

    public function __construct($meetingId)
    {
        $this->meetingId = $meetingId;
    }
    
    public function failed(\Throwable $exception): void
    {
        \Log::error("ProcessMeetingTranscription permanently failed after {$this->tries} attempts for meeting: {$this->meetingId}", [
            'exception' => $exception->getMessage(),
            'file'      => $exception->getFile(),
            'line'      => $exception->getLine(),
            'trace'     => $exception->getTraceAsString(),
        ]);
    }

    public function handle()
    {
        set_time_limit(0);

        \Log::info("STT Job Started (Path 2: Individual Tracks) for meeting: {$this->meetingId}", [
            'attempt' => $this->attempts(),
            'max_tries' => $this->tries,
        ]);

        if (\App\Models\MeetingTranscription::where('meeting_id', $this->meetingId)->exists()) {
            \Log::info("Transcription already exists. Skipping.");
            return; 
        }

        $domain = config('services.metered.domain');
        $secretKey = config('services.metered.secret_key');

        if (empty($domain) || empty($secretKey)) {
            \Log::error("ProcessMeetingTranscription: missing Metered config (services.metered.domain or services.metered.secret_key).");
            $this->fail(new \RuntimeException('Missing Metered API configuration.'));
            return;
        }

        // 1. Fetch all recordings for the room
        $response = \Illuminate\Support\Facades\Http::get("https://{$domain}/api/v1/recordings/room/{$this->meetingId}", [
            'secretKey' => $secretKey
        ]);

        if (!$response->successful()) {
            \Log::error("Metered API request failed for meeting: {$this->meetingId}", [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            $this->release(120);
            return;
        }

        $recordingsData = $response->json('data') ?? [];

        if (empty($recordingsData)) {
            \Log::info("Metered recordings data empty. Releasing job.", ['attempt' => $this->attempts()]);
            $this->release(120);
            return;
        }

        // 2. Filter: Keep ONLY pure 'audio' tracks that belong to a specific user
        \Log::info('Metered API Payload:', $recordingsData);
        $individualTracks = collect($recordingsData)->filter(function ($rec) {
            return ($rec['type'] ?? '') === 'audio' && !empty($rec['participantUsername']);
        });

        if ($individualTracks->isEmpty()) {
            \Log::error("No individual tracks found yet. Releasing...");
            $this->release(120);
            return;
        }

        try {
        $storage = new \Google\Cloud\Storage\StorageClient(['keyFilePath' => config('services.google_cloud.credentials')]);
        $bucket = $storage->bucket(config('services.google_cloud.storage_bucket'));

        $credentials = new \Google\Auth\Credentials\ServiceAccountCredentials(
            'https://www.googleapis.com/auth/cloud-platform',
            config('services.google_cloud.credentials')
        );
        $tokenData = $credentials->fetchAuthToken();
        $token = $tokenData['access_token'] ?? null;
        if (empty($token)) {
            throw new \RuntimeException('Failed to fetch Google auth token — check GOOGLE_APPLICATION_CREDENTIALS.');
        }

        $recordingsDir = storage_path('app/recordings');
        if (!is_dir($recordingsDir)) {
            mkdir($recordingsDir, 0755, true);
        }

        $activeOperations = [];
        $localFiles = [];

        try {
            // 3. Process each track
            foreach ($individualTracks as $track) {
                $recordingId = $track['_id'];
                $participantName = $track['participantUsername'];

                $attendee = \App\Models\MeetingAttendee::where('meeting_id', $this->meetingId)
                    ->where('name', $participantName)
                    ->first();

                $userId = $attendee ? $attendee->user_id : null;

                $downloadUrl = "https://{$domain}/api/v1/recording/{$recordingId}/download?secretKey={$secretKey}";
                $linkResponse = \Illuminate\Support\Facades\Http::get($downloadUrl);

                if (!$linkResponse->successful() || !$linkResponse->json('url')) {
                    \Log::warning("Could not get download URL for track {$recordingId}.", [
                        'status' => $linkResponse->status(),
                        'body'   => $linkResponse->body(),
                    ]);
                    continue;
                }

                $s3Url = $linkResponse->json('url');
                $localVideoPath = storage_path("app/recordings/track_{$recordingId}.mp4");
                $audioPath = storage_path("app/recordings/track_{$recordingId}.flac");

                // Track every file we create so finally{} can clean them up
                $localFiles[] = $localVideoPath;
                $localFiles[] = $audioPath;

                $dlResponse = \Illuminate\Support\Facades\Http::withOptions(['sink' => $localVideoPath])->timeout(300)->get($s3Url);

                if (!$dlResponse->successful() || !file_exists($localVideoPath) || filesize($localVideoPath) === 0) {
                    \Log::error("Failed to download track {$recordingId}.", [
                        'status' => $dlResponse->status(),
                    ]);
                    continue;
                }

                $ffmpeg = exec('which ffmpeg') ?: '/usr/bin/ffmpeg';
                $process = new \Symfony\Component\Process\Process([$ffmpeg, '-y', '-i', $localVideoPath, '-ac', '1', '-ar', '16000', $audioPath]);
                $process->run();

                if (!$process->isSuccessful() || !file_exists($audioPath)) {
                    \Log::error("FFmpeg failed for track {$recordingId}.", [
                        'exit_code' => $process->getExitCode(),
                        'stdout'    => $process->getOutput(),
                        'stderr'    => $process->getErrorOutput(),
                    ]);
                    continue;
                }

                $fileName = "track_{$recordingId}.flac";
                $bucket->upload(fopen($audioPath, 'r'), ['name' => $fileName]);
                $gcsUri = "gs://" . config('services.google_cloud.storage_bucket') . "/{$fileName}";

                // 4. Start Google STT
                $sttResponse = \Illuminate\Support\Facades\Http::withToken($token)
                    ->post('https://speech.googleapis.com/v1/speech:longrunningrecognize', [
                        'config' => [
                            'encoding' => 'FLAC',
                            'sampleRateHertz' => 16000,
                            'languageCode' => 'en-US',
                            'alternativeLanguageCodes' => ['vi-VN'],
                            'enableAutomaticPunctuation' => true,
                        ],
                        'audio' => ['uri' => $gcsUri]
                    ]);

                if (!$sttResponse->successful()) {
                    \Log::error("Google STT request failed for track {$recordingId}.", [
                        'status' => $sttResponse->status(),
                        'body'   => $sttResponse->body(),
                    ]);
                }

                $operationName = $sttResponse->json('name');

                if ($operationName) {
                    \Log::info("Google STT operation started for track {$recordingId}.", ['operation' => $operationName]);
                    $activeOperations[] = [
                        'operation' => $operationName,
                        'user_id'   => $userId,
                    ];
                }
            }

            // 5. Poll all Google STT operations until they are ALL finished
            $allDone = false;
            $attempts = 0;

            while (!$allDone && $attempts < 40) {
                sleep(15);
                $attempts++;
                $allDone = true;

                foreach ($activeOperations as &$op) {
                    if (isset($op['completed']) && $op['completed'] === true) {
                        continue;
                    }

                    $opResponse = \Illuminate\Support\Facades\Http::withToken($token)
                        ->get("https://speech.googleapis.com/v1/operations/{$op['operation']}");

                    $opData = $opResponse->json();

                    if (!empty($opData['done'])) {
                        $op['completed'] = true;
                        if (!empty($opData['error'])) {
                            \Log::error("Google STT operation returned an error.", [
                                'operation' => $op['operation'],
                                'error'     => $opData['error'],
                            ]);
                        } elseif (!empty($opData['response'])) {
                            $this->saveToDatabase($opData['response'], $op['user_id']);
                        } else {
                            \Log::warning("Google STT operation completed but had no response.", ['operation' => $op['operation']]);
                        }
                    } else {
                        $allDone = false;
                    }
                }
            }
            if (!$allDone) {
                \Log::error("STT polling timed out after {$attempts} attempts for meeting: {$this->meetingId}. Some operations may be incomplete.", [
                    'incomplete_operations' => collect($activeOperations)->where('completed', '!==', true)->pluck('operation'),
                ]);
            }
        } finally {
            // 6. Always clean up local files, even if an exception was thrown
            foreach ($localFiles as $file) {
                if (file_exists($file)) {
                    @unlink($file);
                }
            }
            \Log::info("All individual tracks processed and cleaned up for meeting: {$this->meetingId}");
        }

        // 7. Upload composed full meeting video to AWS S3
        $composedRecording = collect($recordingsData)->first(function ($rec) {
            return ($rec['type'] ?? '') === 'video+audio' && ($rec['participantUsername'] ?? '') === 'composer';
        });

        if ($composedRecording) {
            $this->uploadComposedRecordingToS3($composedRecording, $domain, $secretKey);
        } else {
            \Log::info("No composed video recording found for meeting: {$this->meetingId}");
        }
        } catch (\Throwable $e) {
            \Log::error("ProcessMeetingTranscription uncaught exception for meeting: {$this->meetingId}", [
                'exception' => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
            ]);
            throw $e; // re-throw so Laravel retries or calls failed()
        }
    }

    protected function uploadComposedRecordingToS3($recording, $domain, $secretKey)
    {
        $recordingId = $recording['_id'];
        \Log::info("Uploading composed meeting video to S3 for meeting: {$this->meetingId}");

        $downloadUrl = "https://{$domain}/api/v1/recording/{$recordingId}/download?secretKey={$secretKey}";
        $linkResponse = Http::get($downloadUrl);

        if (!$linkResponse->successful() || !$linkResponse->json('url')) {
            \Log::error("Failed to get composed recording download URL for meeting: {$this->meetingId}");
            return;
        }

        $s3Url = $linkResponse->json('url');
        $recordingsDir = storage_path('app/recordings');
        if (!is_dir($recordingsDir)) {
            mkdir($recordingsDir, 0755, true);
        }
        $localPath = storage_path("app/recordings/meeting_{$this->meetingId}_full.mp4");

        $dlResponse = Http::withOptions(['sink' => $localPath])->timeout(600)->get($s3Url);

        if (!$dlResponse->successful() || !file_exists($localPath) || filesize($localPath) === 0) {
            \Log::error("Failed to download composed recording for meeting: {$this->meetingId}");
            return;
        }

        $s3Key = "video-meeting/{$this->meetingId}/full_recording.mp4";
        $uploaded = Storage::disk('s3')->put($s3Key, fopen($localPath, 'r'));

        @unlink($localPath);

        if (!$uploaded) {
            \Log::error("S3 upload failed for meeting: {$this->meetingId}");
            return;
        }

        MeetingHistory::where('meeting_id', $this->meetingId)
            ->update(['recording_url' => $s3Key]);

        \Log::info("Composed meeting video uploaded to S3: {$s3Key}");
    }

    // Updated Database Save Method
    protected function saveToDatabase($response, $userId)
    {
        $results = $response['results'] ?? [];
        if (count($results) === 0) return;

        $fullText = '';

        // Since it's one person, we just concatenate all the results into blocks of text
        foreach ($results as $result) {
            $transcript = $result['alternatives'][0]['transcript'] ?? '';
            if ($transcript) {
                $fullText .= trim($transcript) . ' ';
            }
        }

        if (!empty(trim($fullText))) {
            \App\Models\MeetingTranscription::create([
                'meeting_id' => $this->meetingId,
                'user_id' => $userId, // Assigned directly to the user!
                'text' => trim($fullText)
            ]);
        }
    }
}