<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadHelper
{
    public function upload(UploadedFile $file, $directory = 'uploads', $disk = 'public')
    {
        return $file->store($directory, $disk);
    }

    public function delete($path, $disk = 'public')
    {
        return Storage::disk($disk)->delete($path);
    }
}