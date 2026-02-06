<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FaceService
{
    public function verify(User $user, string $liveImageDataUrl): bool
    {
        try {
            if (!$user->face_image_path)
                return false;

            // File exists?
            if (!Storage::disk('public')->exists($user->face_image_path)) {
                Log::error("Enrolled face missing in storage: {$user->face_image_path}");
                return false;
            }

            // Read enrolled image from storage/app/public/...
            $binary = Storage::disk('public')->get($user->face_image_path);
            $enrolledDataUrl = "data:image/jpeg;base64," . base64_encode($binary);

            $resp = Http::timeout(8)
                ->acceptJson()
                ->post(config('services.face.url') . '/verify-two', [
                    'image_a_base64' => $enrolledDataUrl,
                    'image_b_base64' => $liveImageDataUrl,
                ]);

            if (!$resp->ok()) {
                Log::error("Face service error: status={$resp->status()} body=" . $resp->body());
                return false;
            }

            $json = $resp->json();
            Log::info("Face verify user_id={$user->id} sim=" . ($json['similarity'] ?? 'n/a'));

            return (bool) ($json['match'] ?? false);

        } catch (\Throwable $e) {
            Log::error("FaceService verify error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Keep this if you still upload images sometimes (optional).
     * This does NOT do identity matching.
     */
    public function validateImage($imageFile): bool
    {
        try {
            if ($imageFile instanceof \Illuminate\Http\UploadedFile) {
                if ($imageFile->getSize() < 10240)
                    return false;      // <10KB
                if ($imageFile->getSize() > 5242880)
                    return false;     // >5MB

                $allowed = ['image/jpeg', 'image/jpg', 'image/png'];
                return in_array($imageFile->getMimeType(), $allowed, true);
            }
            return false;
        } catch (\Throwable $e) {
            Log::error('Image validation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate perceptual hash for face comparison
     */
    public function generateHash($file): string
    {
        try {
            if ($file instanceof \Illuminate\Http\UploadedFile) {
                $path = $file->getRealPath();
            } else {
                $path = $file;
            }

            // Resize image to 8x8 for hash generation
            $img = imagecreatefromjpeg($path);
            $resized = imagecreatetruecolor(8, 8);
            imagecopyresampled($resized, $img, 0, 0, 0, 0, 8, 8, imagesx($img), imagesy($img));
            imagedestroy($img);

            // Convert to grayscale and calculate average
            $pixels = [];
            $sum = 0;

            for ($y = 0; $y < 8; $y++) {
                for ($x = 0; $x < 8; $x++) {
                    $rgb = imagecolorat($resized, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;

                    // Convert to grayscale using luminance formula
                    $gray = (int) (0.299 * $r + 0.587 * $g + 0.114 * $b);
                    $pixels[] = $gray;
                    $sum += $gray;
                }
            }

            imagedestroy($resized);

            $avg = $sum / 64;
            $hash = '';

            foreach ($pixels as $pixel) {
                $hash .= ($pixel > $avg) ? '1' : '0';
            }

            return $hash;

        } catch (\Exception $e) {
            Log::error('Hash generation error: ' . $e->getMessage());
            return str_repeat('0', 64); // Return empty hash on error
        }
    }
}
