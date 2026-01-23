<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FaceService;

class FaceRegisterController extends Controller
{
    protected $faceService;

    public function __construct(FaceService $faceService)
    {
        $this->faceService = $faceService;
    }

    public function store(Request $request)
    {
        Log::info('Face registration started', ['user_id' => Auth::id()]);

        try {
            // Set higher execution time
            set_time_limit(30); // 30 seconds max
            
            // Simple validation
            $request->validate([
                'face_image' => 'required|image|mimes:jpeg,png,jpg|max:5120' // 5MB
            ]);

            $user = Auth::user();
            $file = $request->file('face_image');
            
            // Create directory if it doesn't exist
            $directory = 'faces/' . $user->id;
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }
            
            // Generate unique filename
            $filename = 'face_' . time() . '.jpg';
            
            // Save the file
            $path = $file->storeAs($directory, $filename, 'public');
            
            if (!$path) {
                throw new \Exception('Failed to save image');
            }
            
            // Update user
            $user->face_image_path = 'img/' . $path;
            $hash = $this->faceService->generateHash($file);
            $user->face_hash = $hash;
            $user->save();
            
            Log::info('Face registration successful', [
                'user_id' => $user->id,
                'path' => $path,
                'size' => $file->getSize()
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Face registered successfully!',
                'face_path' => 'img/' . $path
            ]);

        } catch (\Exception $e) {
            Log::error('Face registration failed: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }
}