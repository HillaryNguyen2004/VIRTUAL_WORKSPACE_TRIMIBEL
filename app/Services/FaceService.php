<?php

namespace App\Services;

use Softon\LaravelFaceDetect\Facades\FaceDetect;
use Illuminate\Support\Facades\Storage;

class FaceService
{
    /**
     * Save user's face image
     */
    public function saveFace(string $imagePath, string $username): ?string
    {
        try {
            // Create user-specific directory
            $userDir = public_path('img/user_face_detection/' . $username);
            if (!file_exists($userDir)) {
                mkdir($userDir, 0755, true);
            }
            
            // Extract face from image
            $faceDetect = FaceDetect::extract($imagePath);
            
            if (!$faceDetect->face_found) {
                return null;
            }
            
            // Generate unique filename
            $filename = $username . '_' . time() . '.jpg';
            $fullPath = $userDir . '/' . $filename;
            
            // Save cropped face
            $faceDetect->save($fullPath);
            
            return 'img/user_face_detection/' . $username . '/' . $filename;
            
        } catch (\Exception $e) {
            \Log::error('Face save error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verify face against saved face
     */
    public function verifyFace(string $imagePath, string $username): bool
    {
        try {
            // Get user's saved face path
            $userFacePath = $this->getUserFacePath($username);
            
            if (!$userFacePath) {
                return false;
            }
            
            // Extract face from current image
            $currentFace = FaceDetect::extract($imagePath);
            
            if (!$currentFace->face_found) {
                return false;
            }
            
            // Extract face from saved image
            $savedFace = FaceDetect::extract(public_path($userFacePath));
            
            if (!$savedFace->face_found) {
                return false;
            }
            
            // Simple comparison - in production you might want to use more advanced
            // face recognition algorithms or services
            $currentParams = $currentFace->face;
            $savedParams = $savedFace->face;
            
            // Calculate similarity based on face dimensions and position
            $similarity = $this->calculateFaceSimilarity($currentParams, $savedParams);
            
            return $similarity > 0.6; // 60% similarity threshold
            
        } catch (\Exception $e) {
            \Log::error('Face verification error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user's face image path
     */
    private function getUserFacePath(string $username): ?string
    {
        // Search for user's face image in the user-specific directory
        $userDir = public_path('img/user_face_detection/' . $username);
        
        if (!file_exists($userDir)) {
            return null;
        }
        
        $files = scandir($userDir);
        
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && str_contains($file, $username . '_')) {
                return 'img/user_face_detection/' . $username . '/' . $file;
            }
        }
        
        return null;
    }
    
    /**
     * Calculate face similarity
     */
    private function calculateFaceSimilarity(array $face1, array $face2): float
    {
        // Simple similarity calculation based on face dimensions
        $widthDiff = abs($face1['width'] - $face2['width']) / max($face1['width'], $face2['width']);
        $heightDiff = abs($face1['height'] - $face2['height']) / max($face1['height'], $face2['height']);
        $xDiff = abs($face1['x'] - $face2['x']) / max($face1['x'], $face2['x']);
        $yDiff = abs($face1['y'] - $face2['y']) / max($face1['y'], $face2['y']);
        
        // Average similarity (1 - average difference)
        $avgDiff = ($widthDiff + $heightDiff + $xDiff + $yDiff) / 4;
        return 1 - $avgDiff;
    }
}