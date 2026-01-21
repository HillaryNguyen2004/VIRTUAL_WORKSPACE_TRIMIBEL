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
}