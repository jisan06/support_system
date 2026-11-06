<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;

interface MediaDriverInterface
{
    public function store(UploadedFile $file, string $directory): string;

    public function delete(string $path): bool;

    public function url(string $path): string;

    public function exists(string $path): bool;

    public function getDisk(): string;
}
