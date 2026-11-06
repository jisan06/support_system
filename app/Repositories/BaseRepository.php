<?php

namespace App\Repositories;

use App\Repositories\Contracts\BaseRepositoryInterface;

class BaseRepository implements BaseRepositoryInterface
{
    /**
     * The Eloquent model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * Find a record by ID or fail.
     *
     * @param  int  $id
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function find($id)
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Create a new record.
     *
     * @param  array  $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * Update a record by ID.
     *
     * @param  int    $id
     * @param  array  $data
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function update($id, array $data)
    {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }

    /**
     * Delete a record by ID (soft delete if enabled).
     *
     * @param  int  $id
     * @return bool|null
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete($id)
    {
        return $this->find($id)->delete();
    }
}
