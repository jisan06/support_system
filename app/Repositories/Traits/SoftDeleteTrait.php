<?php

namespace App\Repositories\Traits;

/**
 * Trait SoftDeleteTrait
 *
 * Provides reusable methods for handling
 * soft deletes in repositories.
 *
 * Usage:
 * - Add this trait in a repository that works
 *   with Eloquent models using SoftDeletes.
 */
trait SoftDeleteTrait
{
    /**
     * Restore a soft deleted model by its ID.
     *
     * @param  int  $id
     * @return bool|null
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function restore($id)
    {
        return $this->model->withTrashed()->findOrFail($id)->restore();
    }

    /**
     * Permanently delete a soft deleted model by its ID.
     *
     * @param  int  $id
     * @return bool|null
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function forceDelete($id)
    {
        return $this->model->withTrashed()->findOrFail($id)->forceDelete();
    }
}
