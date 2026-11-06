<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\MediaUploadRequest;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    protected MediaService $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * Display media grid page.
     */
    public function index(Request $request)
    {
        $query = Media::query()->latest();

        if ($request->filled('collection')) {
            $query->where('collection', $request->collection);
        }

        if ($request->filled('search')) {
            $query->where('file_name', 'like', '%' . $request->search . '%');
        }

        // AJAX request — return JSON for infinite scroll / filtering
        if ($request->ajax()) {
            $media = $query->paginate(24);

            $items = $media->getCollection()->map(function ($item) {
                return [
                    'id'        => $item->id,
                    'file_name' => $item->file_name,
                    'mime_type' => $item->mime_type,
                    'size'      => $item->size,
                    'collection'=> $item->collection,
                    'url'       => Storage::disk($item->disk)->url($item->path),
                    'is_image'  => str_starts_with($item->mime_type, 'image/'),
                ];
            });

            return response()->json([
                'data'         => $items,
                'next_page'    => $media->nextPageUrl(),
                'current_page' => $media->currentPage(),
                'last_page'    => $media->lastPage(),
                'total'        => $media->total(),
            ]);
        }

        $collections = Media::select('collection')
            ->distinct()
            ->orderBy('collection')
            ->pluck('collection');

        return view('admin.media.index', compact('collections'));
    }

    /**
     * Upload media via AJAX.
     */
    public function store(MediaUploadRequest $request)
    {
        $collection = $request->input('collection', 'default');

        $media = $this->mediaService->upload(
            $request->file('file'),
            Media::class,
            0,
            $collection
        );

        return ResponseHelper::success([
            'id'        => $media->id,
            'file_name' => $media->file_name,
            'mime_type' => $media->mime_type,
            'size'      => $media->size,
            'collection'=> $media->collection,
            'url'       => Storage::disk($media->disk)->url($media->path),
            'is_image'  => str_starts_with($media->mime_type, 'image/'),
        ], __('media.media_uploaded'));
    }

    /**
     * Remove the specified media.
     */
    public function destroy(Media $medium)
    {
        $this->mediaService->delete($medium->id);

        return ResponseHelper::success([], __('media.media_deleted'));
    }

    /**
     * Bulk delete media items.
     */
    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return response()->json(['status' => 'error', 'message' => __('common.bulk_empty_warning')], 422);
        }

        foreach ($ids as $id) {
            $this->mediaService->delete($id);
        }

        return ResponseHelper::success([], __('media.bulk_deleted'));
    }
}
