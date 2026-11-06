<?php

namespace App\Services;

use App\Repositories\Contracts\MediaRepositoryInterface;
use App\Services\Media\MediaDriverManager;
use Illuminate\Http\UploadedFile;

class MediaService extends BaseService
{
    protected MediaDriverManager $driverManager;

    public function __construct(
        MediaRepositoryInterface $repository,
        MediaDriverManager $driverManager
    ) {
        parent::__construct($repository);
        $this->driverManager = $driverManager;
    }

    /**
     * Upload a file and create a media record.
     */
    public function upload(
        UploadedFile $file,
        string $mediableType,
        int $mediableId,
        string $collection = 'default',
        int $sortOrder = 0
    ) {
        $driver = $this->driverManager->driver();
        $directory = config('media.path_prefix') . '/' . $collection;
        $path = $driver->store($file, $directory);

        return $this->repository->create([
            'mediable_type' => $mediableType,
            'mediable_id'   => $mediableId,
            'collection'    => $collection,
            'file_name'     => $file->getClientOriginalName(),
            'disk'          => $driver->getDisk(),
            'path'          => $path,
            'mime_type'     => $file->getMimeType(),
            'size'          => $file->getSize(),
            'sort_order'    => $sortOrder,
        ]);
    }

    /**
     * Delete a media record and its file.
     */
    public function delete($id)
    {
        $media = $this->repository->find($id);
        $this->driverManager->driver()->delete($media->path);

        return $media->delete();
    }

    /**
     * Get the public URL for a media record.
     */
    public function getUrl($id): string
    {
        $media = $this->repository->find($id);

        return $this->driverManager->driver()->url($media->path);
    }

    /**
     * Get all media for a model, optionally by collection.
     */
    public function getByMediable(string $mediableType, int $mediableId, ?string $collection = null)
    {
        return $this->repository->findByMediable($mediableType, $mediableId, $collection);
    }

    /**
     * Delete all media for a model and their files.
     */
    public function deleteByMediable(string $mediableType, int $mediableId, ?string $collection = null): int
    {
        $mediaItems = $this->repository->findByMediable($mediableType, $mediableId, $collection);
        $driver = $this->driverManager->driver();

        foreach ($mediaItems as $media) {
            $driver->delete($media->path);
        }

        return $this->repository->deleteByMediable($mediableType, $mediableId, $collection);
    }
}
