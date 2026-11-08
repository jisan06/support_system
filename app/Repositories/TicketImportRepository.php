<?php

namespace App\Repositories;

use App\Models\TicketImport;
use App\Repositories\Contracts\TicketImportRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TicketImportRepository extends BaseRepository implements TicketImportRepositoryInterface
{
    public function __construct(TicketImport $model)
    {
        parent::__construct($model);
    }

    /**
     * Get paginated ticket imports ordered by latest.
     */
    public function getPaginatedImports(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with('uploader:id,name,email')
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * Mark stale imports stuck in pending or processing as failed.
     */
    public function markStaleImportsAsFailed(int $staleHours = 2): int
    {
        return $this->model->newQuery()
            ->whereIn('status', ['pending', 'processing'])
            ->where('updated_at', '<', now()->subHours($staleHours))
            ->update([
                'status' => 'failed',
                'errors' => [
                    [
                        'row'     => 0,
                        'email'   => '',
                        'subject' => '',
                        'type'    => 'Timeout',
                        'reason'  => "Import process timed out or was interrupted after {$staleHours} hours.",
                    ],
                ],
            ]);
    }

    /**
     * Find import with uploader loaded.
     */
    public function findWithUploader(int $id): TicketImport
    {
        return $this->model->newQuery()
            ->with('uploader:id,name,email')
            ->findOrFail($id);
    }
}
