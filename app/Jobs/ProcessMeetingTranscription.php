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
use Google\Cloud\Speech\V2\Client\SpeechClient;
use Google\Cloud\Speech\V2\BatchRecognizeRequest;
use Google\Cloud\Speech\V2\BatchRecognizeFileMetadata;
use Google\Cloud\Speech\V2\RecognitionConfig;
use Google\Cloud\Speech\V2\RecognitionFeatures;
use Google\Cloud\Speech\V2\AutoDetectDecodingConfig;
use Google\Cloud\Speech\V2\RecognitionOutputConfig;
use Google\Cloud\Speech\V2\InlineOutputConfig;
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

        $projectId  = config('services.google_cloud.project_id');
        $location   = config('services.google_cloud.stt_location', 'asia-south1'); // Chirp 3 preview region
        $recognizer = "projects/{$projectId}/locations/{$location}/recognizers/_";

        // Regional endpoint must match the location in the recognizer name.
        // 'global' uses the default endpoint; all other locations use {location}-speech.googleapis.com
        $apiEndpoint = $location === 'global'
            ? 'speech.googleapis.com'
            : "{$location}-speech.googleapis.com";

        // V2 SDK handles auth via credentials file — no manual token needed
        $speechClient = new SpeechClient([
            'credentials' => config('services.google_cloud.credentials'),
            'apiEndpoint' => $apiEndpoint,
        ]);

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

                // 4. Start Google STT V2 (Chirp 3) via SDK
                $batchRequest = new BatchRecognizeRequest([
                    'recognizer' => $recognizer,
                    'config'     => new RecognitionConfig([
                        'model'                => 'chirp_3',
                        'language_codes'       => ['en-US', 'vi-VN'],
                        'auto_decoding_config' => new AutoDetectDecodingConfig(),
                        'features'             => new RecognitionFeatures([
                            'enable_automatic_punctuation' => true,
                        ]),
                    ]),
                    'files' => [
                        new BatchRecognizeFileMetadata(['uri' => $gcsUri]),
                    ],
                    'recognition_output_config' => new RecognitionOutputConfig([
                        'inline_response_config' => new InlineOutputConfig(),
                    ]),
                ]);

                $operation = $speechClient->batchRecognize($batchRequest);
                \Log::info("Google STT V2 operation started for track {$recordingId}.");

                $activeOperations[] = [
                    'operation' => $operation,
                    'user_id'   => $userId,
                    'gcs_uri'   => $gcsUri,
                ];
            }

            // 5. Poll all Google STT V2 operations until they are ALL finished
            $allDone  = false;
            $attempts = 0;

            while (!$allDone && $attempts < 40) {
                sleep(15);
                $attempts++;
                $allDone = true;

                foreach ($activeOperations as &$op) {
                    if (isset($op['completed']) && $op['completed'] === true) {
                        continue;
                    }

                    // Reload the operation status from Google
                    $op['operation']->reload();

                    if ($op['operation']->isDone()) {
                        $op['completed'] = true;

                        if ($op['operation']->operationSucceeded()) {
                            /** @var \Google\Cloud\Speech\V2\BatchRecognizeResponse $result */
                            $result = $op['operation']->getResult();
                            $this->saveToDatabase($result, $op['user_id'], $op['gcs_uri']);
                        } else {
                            $error = $op['operation']->getError();
                            \Log::error("Google STT V2 operation failed.", [
                                'error' => $error ? $error->getMessage() : 'unknown',
                            ]);
                        }
                    } else {
                        $allDone = false;
                    }
                }
            }

            if (!$allDone) {
                \Log::error("STT polling timed out after {$attempts} attempts for meeting: {$this->meetingId}. Some operations may be incomplete.");
            }
        } finally {
            // 6. Always clean up local files and close the SDK client
            foreach ($localFiles as $file) {
                if (file_exists($file)) {
                    @unlink($file);
                }
            }
            $speechClient->close();
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

    // V2 Database Save — parses BatchRecognizeResponse from the V2 SDK
    protected function saveToDatabase(\Google\Cloud\Speech\V2\BatchRecognizeResponse $response, $userId, string $gcsUri)
    {
        // V2 returns a map of file URI → BatchRecognizeFileResult
        $fileResults = $response->getResults();

        // We send one file per request, so grab the first (and only) result
        $fileResult = null;
        foreach ($fileResults as $result) {
            $fileResult = $result;
            break;
        }

        if (!$fileResult) {
            \Log::warning("STT V2: no file result found in BatchRecognizeResponse for meeting: {$this->meetingId}");
            return;
        }

        // InlineResult contains the actual transcript segments
        $inlineResult = $fileResult->getTranscript();
        if (!$inlineResult) {
            \Log::warning("STT V2: file result has no inline transcript for meeting: {$this->meetingId}");
            return;
        }

        $fullText = '';

        // Concatenate all recognition result segments into one block of text
        foreach ($inlineResult->getResults() as $result) {
            $alternatives = $result->getAlternatives();
            if (count($alternatives) > 0) {
                $text = $alternatives[0]->getTranscript();
                if ($text) {
                    $fullText .= trim($text) . ' ';
                }
            }
        }

        if (!empty(trim($fullText))) {
            \App\Models\MeetingTranscription::create([
                'meeting_id' => $this->meetingId,
                'user_id'    => $userId,
                'text'       => trim($fullText),
            ]);
        }
    }
}