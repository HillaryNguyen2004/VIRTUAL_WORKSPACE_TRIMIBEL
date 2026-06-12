<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Services\FaceService;

class ProcessFaceRegistration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $imagePath;

    public function __construct(User $user, string $imagePath)
    {
        $this->user = $user;
        $this->imagePath = $imagePath;
    }

    public function handle(FaceService $faceService)
    {
        $savedPath = $faceService->saveFace($this->imagePath, $this->user->username);
        
        if ($savedPath) {
            $this->user->update([
                'face_image_path' => $savedPath,
                'face_registered_at' => now()
            ]);
        }
        
        // Clean up temp file
        if (file_exists($this->imagePath)) {
            unlink($this->imagePath);
        }
    }
}