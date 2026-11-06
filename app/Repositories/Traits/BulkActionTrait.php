<?php

namespace App\Repositories\Traits;

trait BulkActionTrait
{
    /**
     * Bulk delete records by IDs.
     *
     * @param  array  $ids
     * @return int
     */
    public function bulkDelete(array $ids): int
    {
        return $this->model->whereIn('id', $ids)->delete();
    }

    /**
     * Bulk restore soft deleted records by IDs.
     *
     * @param  array  $ids
     * @return int
     */
    public function bulkRestore(array $ids): int
    {
        return $this->model->withTrashed()->whereIn('id', $ids)->restore();
    }

    /**
     * Bulk force delete records by IDs.
     *
     * @param  array  $ids
     * @return int
     */
    public function bulkForceDelete(array $ids): int
    {
        return $this->model->withTrashed()->whereIn('id', $ids)->forceDelete();
    }
}
