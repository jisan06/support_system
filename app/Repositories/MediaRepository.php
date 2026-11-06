<?php

namespace App\Repositories;

use App\Models\Media;
use App\Repositories\Contracts\MediaRepositoryInterface;

class MediaRepository extends BaseRepository implements MediaRepositoryInterface
{
    public function __construct(Media $model)
    {
        parent::__construct($model);
    }

    public function findByMediable(string $mediableType, int $mediableId, ?string $collection = null)
    {
        $query = $this->model
            ->where('mediable_type', $mediableType)
            ->where('mediable_id', $mediableId);

        if ($collection) {
            $query->where('collection', $collection);
        }

        return $query->orderBy('sort_order')->get();
    }

    public function deleteByMediable(string $mediableType, int $mediableId, ?string $collection = null): int
    {
        $query = $this->model
            ->where('mediable_type', $mediableType)
            ->where('mediable_id', $mediableId);

        if ($collection) {
            $query->where('collection', $collection);
        }

        return $query->delete();
    }
}
