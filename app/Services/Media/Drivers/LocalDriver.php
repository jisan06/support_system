<?php

namespace App\Services\Media\Drivers;

use App\Services\Media\MediaDriverInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class LocalDriver implements MediaDriverInterface
{
    protected string $disk;

    public function __construct(string $disk)
    {
        $this->disk = $disk;
    }

    public function store(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, $this->disk);
    }

    public function delete(string $path): bool
    {
        return Storage::disk($this->disk)->delete($path);
    }

    public function url(string $path): string
    {
        if ($this->disk === 'public') {
            return asset('storage/' . $path);
        }

        return Storage::disk($this->disk)->url($path);
    }

    public function exists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path);
    }

    public function getDisk(): string
    {
        return $this->disk;
    }
}
