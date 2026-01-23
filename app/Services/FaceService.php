<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FaceService
{
    /**
     * Basic validation to ensure image might contain a face
     */
    public function validateImage($imageFile): bool
    {
        try {
            if ($imageFile instanceof \Illuminate\Http\UploadedFile) {
                // Check file size
                if ($imageFile->getSize() < 10240) { // Less than 10KB
                    Log::info('Image too small');
                    return false;
                }
                
                if ($imageFile->getSize() > 5242880) { // More than 5MB
                    Log::info('Image too large');
                    return false;
                }
                
                // Check MIME type
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                if (!in_array($imageFile->getMimeType(), $allowedTypes)) {
                    Log::info('Invalid file type: ' . $imageFile->getMimeType());
                    return false;
                }
                
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::error('Image validation error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Simple image comparison (for future check-in verification)
     */
    public function compareImages($image1Path, $image2Path): bool
    {
        try {
            // For now, just check if both files exist
            // In production, you'd implement actual comparison
            return file_exists($image1Path) && file_exists($image2Path);
            
        } catch (\Exception $e) {
            Log::error('Image comparison error: ' . $e->getMessage());
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
                    $gray = (int)(0.299 * $r + 0.587 * $g + 0.114 * $b);
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
    
    /**
     * Compare two hashes and return similarity score (0-1)
     */
    public function compareHashes(string $hash1, string $hash2): float
    {
        if (strlen($hash1) !== strlen($hash2)) {
            return 0;
        }
        
        $similar = 0;
        $length = strlen($hash1);
        
        for ($i = 0; $i < $length; $i++) {
            if ($hash1[$i] === $hash2[$i]) {
                $similar++;
            }
        }
        
        return $similar / $length;
    }
}