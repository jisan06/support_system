<?php

namespace App\Services;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Support\Facades\DB;

abstract class BaseService
{
    protected BaseRepositoryInterface $repository;

    public function __construct(BaseRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function transaction(callable $callback)
    {
        return DB::transaction($callback);
    }

    public function find($id)
    {
        return $this->repository->find($id);
    }

    public function create(array $data)
    {
        return $this->transaction(fn () => $this->repository->create($data));
    }

    public function update($id, array $data)
    {
        return $this->transaction(fn () => $this->repository->update($id, $data));
    }

    public function delete($id)
    {
        return $this->transaction(fn () => $this->repository->delete($id));
    }
}