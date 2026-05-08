<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChatFileService
{
    protected $allowedTypes = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain', 'text/csv',
        'application/zip', 'application/x-zip-compressed',
        'application/x-rar-compressed', 'application/rar',
        'application/octet-stream', // fallback MIME when browser can't detect
    ];

    protected $maxFileSize = 41943040; // 40MB in bytes

    /**
     * Upload file for chat
     */
    public function uploadFile(UploadedFile $file, $conversationId)
    {
        // Validate file
        $this->validateFile($file);

        // Generate unique filename
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $filename = Str::random(20) . '_' . time() . '.' . $extension;
        
        // Store file in chat uploads directory
        $directory = 'chat/files/' . date('Y/m');
        $path = $file->storeAs($directory, $filename, config('filesystems.default'));

        return [
            'file_name' => $originalName,
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'file_type' => $file->getMimeType()
        ];
    }

    /**
     * Upload image for chat
     */
    public function uploadImage(UploadedFile $file, $conversationId)
    {
        // Validate image
        $this->validateImage($file);

        // Generate unique filename
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $filename = Str::random(20) . '_' . time() . '.' . $extension;
        
        // Store image in chat images directory
        $directory = 'chat/images/' . date('Y/m');
        $path = $file->storeAs($directory, $filename, config('filesystems.default'));

        return [
            'file_name' => $originalName,
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'file_type' => $file->getMimeType()
        ];
    }

    /**
     * Validate uploaded file
     */
    protected function validateFile(UploadedFile $file)
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('Invalid file upload');
        }

        if ($file->getSize() > $this->maxFileSize) {
            throw new \InvalidArgumentException('File size exceeds maximum limit of 40MB');
        }

        if (!in_array($file->getMimeType(), $this->allowedTypes)) {
            throw new \InvalidArgumentException('File type not allowed');
        }
    }

    /**
     * Validate uploaded image
     */
    protected function validateImage(UploadedFile $file)
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('Invalid image upload');
        }

        if ($file->getSize() > $this->maxFileSize) {
            throw new \InvalidArgumentException('Image size exceeds maximum limit of 40MB');
        }

        $imageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $imageTypes)) {
            throw new \InvalidArgumentException('File must be an image (JPEG, PNG, GIF, WEBP)');
        }

        // Additional image validation
        $imageInfo = getimagesize($file->getRealPath());
        if (!$imageInfo) {
            throw new \InvalidArgumentException('Invalid image file');
        }
    }

    /**
     * Delete file
     */
    public function deleteFile($filePath)
    {
        if ($filePath && Storage::disk()->exists($filePath)) {
            return Storage::disk()->delete($filePath);
        }
        return false;
    }

    /**
     * Get allowed file types for frontend validation
     */
    public function getAllowedTypes()
    {
        return $this->allowedTypes;
    }

    /**
     * Get max file size
     */
    public function getMaxFileSize()
    {
        return $this->maxFileSize;
    }

    /**
     * Get max file size in readable format
     */
    public function getMaxFileSizeFormatted()
    {
        return '40MB';
    }
}