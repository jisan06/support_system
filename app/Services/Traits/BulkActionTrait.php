<?php

namespace App\Services\Traits;

trait BulkActionTrait
{
    /**
     * Bulk soft delete
     */
    public function bulkDelete(array $ids)
    {
        return $this->repository->bulkDelete($ids);
    }

    /**
     * Bulk restore
     */
    public function bulkRestore(array $ids)
    {
        return $this->repository->bulkRestore($ids);
    }

    /**
     * Bulk force delete
     */
    public function bulkForceDelete(array $ids)
    {
        return $this->repository->bulkForceDelete($ids);
    }
}
