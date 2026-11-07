<?php

namespace App\Models\Traits;

use App\Models\Media;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

trait HasMedia
{
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->orderBy('sort_order');
    }

    public function mediaByCollection(string $collection = 'default'): MorphMany
    {
        return $this->media()->where('collection', $collection);
    }

    public function getFirstMedia(string $collection = 'default'): ?Media
    {
        return $this->mediaByCollection($collection)->first();
    }

    public function getFirstMediaUrl(string $collection = 'default'): ?string
    {
        $media = $this->getFirstMedia($collection);

        if (!$media) {
            return null;
        }

        if ($media->disk === 'public') {
            return asset('storage/' . $media->path);
        }

        return Storage::disk($media->disk)->url($media->path);
    }
}
