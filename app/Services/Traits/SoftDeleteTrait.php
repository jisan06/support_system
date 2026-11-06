<?php

namespace App\Services\Traits;

trait SoftDeleteTrait
{
    /**
     * Restore a soft-deleted record by ID
     */
    public function restore($id)
    {
        return $this->repository->restore($id);
    }

    /**
     * Permanently delete a soft-deleted record by ID
     */
    public function forceDelete($id)
    {
        return $this->repository->forceDelete($id);
    }
}
