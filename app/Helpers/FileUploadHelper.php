<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadHelper
{
    public function upload(UploadedFile $file, $directory = 'uploads', $disk = null)
    {
        $diskName = $disk ?: config('filesystems.default');
        return $file->store($directory, $diskName);
    }

    public function delete($path, $disk = null)
    {
        $diskName = $disk ?: config('filesystems.default');
        return Storage::disk($diskName)->delete($path);
    }
}


