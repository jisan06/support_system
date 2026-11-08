<?php

namespace App\Repositories\Contracts;

use App\Models\TicketImport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TicketImportRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get paginated ticket imports ordered by latest.
     */
    public function getPaginatedImports(int $perPage = 15): LengthAwarePaginator;

    /**
     * Find import with uploader loaded.
     */
    public function findWithUploader(int $id): TicketImport;

    /**
     * Mark stale imports stuck in pending or processing as failed.
     */
    public function markStaleImportsAsFailed(int $staleHours = 2): int;
}

