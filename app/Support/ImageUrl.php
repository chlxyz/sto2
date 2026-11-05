<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class ImageUrl
{
    // The bucket is private, so every response must carry a signed link.
    public static function make(?string $path): ?string
    {
        return $path ? Storage::disk('minio')->temporaryUrl($path, now()->addHour()) : null;
    }
}
