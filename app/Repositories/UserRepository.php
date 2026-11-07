<?php

namespace App\Repositories;

use App\Models\Media;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Traits\BulkActionTrait;
use App\Repositories\Traits\SoftDeleteTrait;
use Illuminate\Database\Eloquent\Collection;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    use SoftDeleteTrait, BulkActionTrait;

    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function getActive()
    {
        return $this->getActiveAdmins();
    }

    public function getActiveAdmins(): Collection
    {
        return $this->model->newQuery()
            ->where('account_type', User::TYPE_ADMIN)
            ->where('status', 'Active')
            ->select('id', 'name')
            ->get();
    }

    public function getActiveCustomers(): Collection
    {
        return $this->model->newQuery()
            ->where('account_type', User::TYPE_CUSTOMER)
            ->where('status', 'Active')
            ->select('id', 'name', 'email')
            ->get();
    }

    public function findAdmin(int|string $id): User
    {
        return $this->model->newQuery()
            ->where('account_type', User::TYPE_ADMIN)
            ->findOrFail($id);
    }

    public function findCustomer(int|string $id): User
    {
        return $this->model->newQuery()
            ->where('account_type', User::TYPE_CUSTOMER)
            ->findOrFail($id);
    }

    /**
     * Create a new user (admin or customer).
     *
     * @param  array  $data
     * @return User
     */
    public function create(array $data)
    {
        $created_by = auth()->id();
        $accountType = $data['account_type'] ?? User::TYPE_ADMIN;
        $data['account_type'] = $accountType;
        $data['created_by'] = $created_by;

        // Extract and unset roles
        $roles = $data['role_ids'] ?? null;
        unset($data['role_ids']);

        // Extract and unset avatar
        $avatarMediaId = $data['avatar_media_id'] ?? null;
        unset($data['avatar_media_id']);

        // Clean up confirm password field
        unset($data['password_confirm']);

        // Create user
        $user = $this->model->create($data);

        // Attach roles strictly for internal admin users
        if ($roles !== null && $user->isAdmin()) {
            $user->roles()->attach($roles, [
                'created_by' => $created_by,
            ]);
        }

        // Attach avatar media
        $this->syncAvatar($user, $avatarMediaId);

        return $user;
    }

    /**
     * Update an existing user (admin or customer).
     *
     * @param  int|string  $id
     * @param  array  $data
     * @return User
     */
    public function update($id, array $data)
    {
        $user = $this->find($id);

        // Prevent unauthorized account_type mutation
        unset($data['account_type']);

        // Clean up confirm password field
        unset($data['password_confirm']);

        // Extract and unset roles
        $roles = $data['role_ids'] ?? null;
        unset($data['role_ids']);

        // Extract and unset avatar
        $avatarMediaId = $data['avatar_media_id'] ?? null;
        unset($data['avatar_media_id']);

        // Handle password: only update if provided
        if (empty($data['password'])) {
            unset($data['password']);
        }

        // Update user fields
        $user->update($data);

        // Sync roles strictly for internal admin users
        if ($roles !== null && $user->isAdmin()) {
            $user->roles()->syncWithPivotValues($roles, ['created_by' => auth()->id()]);
        }

        // Sync avatar media
        $this->syncAvatar($user, $avatarMediaId);

        return $user;
    }

    /**
     * Sync avatar media attachment via polymorphic relation.
     */
    protected function syncAvatar(User $user, $avatarMediaId): void
    {
        // Detach old avatar
        $user->mediaByCollection('avatar')->update([
            'mediable_type' => Media::class,
            'mediable_id'   => 0,
        ]);

        // Attach new avatar
        if ($avatarMediaId) {
            Media::where('id', $avatarMediaId)->update([
                'mediable_type' => User::class,
                'mediable_id'   => $user->id,
                'collection'    => 'avatar',
            ]);
        }
    }
}
