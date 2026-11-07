<?php

namespace App\Repositories;

use App\Models\Media;
use App\Models\User;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Traits\BulkActionTrait;
use App\Repositories\Traits\SoftDeleteTrait;

class CustomerRepository extends BaseRepository implements CustomerRepositoryInterface
{
    use SoftDeleteTrait, BulkActionTrait;

    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function find($id)
    {
        return $this->model->newQuery()->where('account_type', User::TYPE_CUSTOMER)->findOrFail($id);
    }

    public function getActive()
    {
        return $this->model->newQuery()->where('account_type', User::TYPE_CUSTOMER)->where('status', 'Active')->select('id', 'name', 'email')->get();
    }

    public function create(array $data)
    {
        $created_by = auth()->id();

        // Strictly enforce account_type is customer
        $data['account_type'] = User::TYPE_CUSTOMER;
        $data['created_by'] = $created_by;

        $avatarMediaId = $data['avatar_media_id'] ?? null;
        unset($data['avatar_media_id'], $data['role_ids'], $data['password_confirm']);

        $customer = $this->model->create($data);

        $this->syncAvatar($customer, $avatarMediaId);

        return $customer;
    }

    public function update($id, array $data)
    {
        $customer = $this->find($id);

        $avatarMediaId = $data['avatar_media_id'] ?? null;
        unset($data['avatar_media_id'], $data['role_ids'], $data['account_type'], $data['password_confirm']);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $customer->update($data);

        $this->syncAvatar($customer, $avatarMediaId);

        return $customer;
    }

    public function restore($id)
    {
        return $this->model->withTrashed()->where('account_type', User::TYPE_CUSTOMER)->findOrFail($id)->restore();
    }

    public function forceDelete($id)
    {
        return $this->model->withTrashed()->where('account_type', User::TYPE_CUSTOMER)->findOrFail($id)->forceDelete();
    }

    public function bulkDelete(array $ids): int
    {
        return $this->model->newQuery()->where('account_type', User::TYPE_CUSTOMER)->whereIn('id', $ids)->delete();
    }

    public function bulkRestore(array $ids): int
    {
        return $this->model->withTrashed()->where('account_type', User::TYPE_CUSTOMER)->whereIn('id', $ids)->restore();
    }

    public function bulkForceDelete(array $ids): int
    {
        return $this->model->withTrashed()->where('account_type', User::TYPE_CUSTOMER)->whereIn('id', $ids)->forceDelete();
    }

    protected function syncAvatar(User $customer, $avatarMediaId): void
    {
        $old = $customer->mediaByCollection('avatar')->first();

        if ($avatarMediaId) {
            if (!$old || $old->id !== (int) $avatarMediaId) {
                if ($old) {
                    $customer->media()->detach($old->id);
                }
                $customer->media()->attach($avatarMediaId, ['collection' => 'avatar']);
            }
        } elseif ($avatarMediaId === null && $old) {
            $customer->media()->detach($old->id);
        }
    }
}

