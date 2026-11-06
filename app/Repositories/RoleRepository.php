<?php
namespace App\Repositories;

use App\Models\Role;
use App\Models\RolePermission;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Traits\BulkActionTrait;
use App\Repositories\Traits\SoftDeleteTrait;

class RoleRepository extends BaseRepository implements RoleRepositoryInterface
{
    use SoftDeleteTrait, BulkActionTrait;

    public function __construct(Role $model)
    {
        parent::__construct($model);
    }

    public function getActive($minLevel = null)
    {
        $query = $this->model->where('status', 'Active')->select('id', 'name', 'level');

        if ($minLevel !== null) {
            $query->where('level', '>=', $minLevel);
        }

        return $query->get();
    }

    public function permission(Role $role, array $data)
    {
        // If no permissions sent, just clear all
        if (empty($data['permissions'])) {
            return $role->permissions()->delete();
        }

        // Prepare new permissions
        $permissions = collect($data['permissions'])->map(fn($slug) => [
            'role_id' => $role->id,
            'slug' => $slug,
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        // Delete old ones that are not in the new list
        $role->permissions()
            ->whereNotIn('slug', $data['permissions'])
            ->delete();

        // Insert/update the new ones
        return RolePermission::upsert($permissions, ['role_id', 'slug'], ['updated_at']);
    }
}
