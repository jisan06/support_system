<?php
namespace App\Repositories;

use App\Models\Media;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Traits\BulkActionTrait;
use App\Repositories\Traits\SoftDeleteTrait;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    use SoftDeleteTrait, BulkActionTrait;

    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function getActive()
    {
        return $this->model->where('status', 'Active')->select('id', 'name')->get();
    }

    /**
     * Create a new record.
     *
     * @param  array  $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $data)
    {
        $created_by = auth()->id();
        // extract roles from payload if present
        $roles = $data['role_ids'] ?? null;
        unset($data['role_ids']);

        // extract avatar media id
        $avatarMediaId = $data['avatar_media_id'] ?? null;
        unset($data['avatar_media_id']);

        // create user
        $data['created_by'] = $created_by;
        $user = $this->model->create($data);

        // attach roles if provided
        if ($roles !== null) {
            $user->roles()->attach($roles, [
                'created_by' => $created_by,
            ]);
        }

        // attach avatar media
        $this->syncAvatar($user, $avatarMediaId);

        return $user;
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
        $user = $this->find($id);

        // extract roles from payload if present
        $roles = $data['role_ids'] ?? null;
        unset($data['role_ids']);

        // extract avatar media id
        $avatarMediaId = $data['avatar_media_id'] ?? null;
        unset($data['avatar_media_id']);

        // handle password: only update if provided
        if (empty($data['password'])) {
            unset($data['password']);
        }

        // update user fields
        $user->update($data);

        // sync roles if provided
        if ($roles !== null) {
            $user->roles()->syncWithPivotValues($roles, ['created_by' => auth()->id()]);
        }

        // sync avatar media
        $this->syncAvatar($user, $avatarMediaId);

        return $user;
    }

    protected function syncAvatar(User $user, $avatarMediaId): void
    {
        // detach old avatar
        $user->mediaByCollection('avatar')->update([
            'mediable_type' => Media::class,
            'mediable_id'   => 0,
        ]);

        // attach new avatar
        if ($avatarMediaId) {
            Media::where('id', $avatarMediaId)->update([
                'mediable_type' => User::class,
                'mediable_id'   => $user->id,
                'collection'    => 'avatar',
            ]);
        }
    }
}
