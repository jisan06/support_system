<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\RolesDataTable;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkActionRequest;
use App\Http\Requests\RoleRequest;
use App\Models\Role;
use App\Services\RoleService;
use App\ViewModels\Admin\RoleFormData;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    protected RoleService $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    protected function authorizeRoleAccess(Role $role): void
    {
        if ($role->level < auth()->user()->roleLevel()) {
            abort(403);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(RolesDataTable $dataTable)
    {
        $data = [
            'page_type' => 'index',
            'bulk_actions' => [
                (object)['value' => 'trash', 'label' => __('common.move_trash')]
            ],
            'bulk_delete_url' => route('roles.bulk_delete'),
        ];
        return $dataTable->render('admin.role.index', $data);
    }

    /**
     * Display a listing of the deleted resource.
     */
    public function trash(RolesDataTable $dataTable)
    {
        $data = [
            'page_type' => 'trash',
            'bulk_actions' => [
                (object)['value' => 'restore', 'label' => __('common.restore')],
                (object)['value' => 'force_delete', 'label' => __('common.delete_permanently')],
            ],
            'bulk_restore_url' => route('roles.bulk_restore'),
            'bulk_delete_url' => route('roles.bulk_force_delete'),
        ];
        return $dataTable->render('admin.role.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.role.form',
            RoleFormData::forCreate()->toArray()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoleRequest $request)
    {
        $this->roleService->create($request->validated());
        return redirect()->route('roles.index')
            ->with('success', __('role.role_created'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        $this->authorizeRoleAccess($role);
        return view('admin.role.form',
            RoleFormData::forEdit($role)->toArray()
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoleRequest $request, Role $role)
    {
        $this->authorizeRoleAccess($role);
        $this->roleService->update($role->id, $request->validated());
        return redirect()->route('roles.index')
            ->with('success', __('role.role_updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        $this->authorizeRoleAccess($role);
        if (auth()->user()->roles->contains('id', $role->id)) {
            return ResponseHelper::error(__('role.cannot_delete_own_role'), 403);
        }
        $this->roleService->delete($role->id);
        return ResponseHelper::success([], __('common.deleted'));
    }

    /**
     * Remove bulk item from storage.
     */
    public function bulkDestroy(BulkActionRequest $request)
    {
        $userRoleIds = auth()->user()->roles->pluck('id')->toArray();
        $ids = $this->filterAccessibleRoleIds(
            array_diff($request->validated('ids'), $userRoleIds)
        );
        if (empty($ids)) {
            return ResponseHelper::error(__('role.cannot_delete_own_role'), 403);
        }
        $this->roleService->bulkDelete($ids);
        return ResponseHelper::success([], __('common.deleted'));
    }

    /**
     * Restore the deleted resource
     */
    public function restore($id)
    {
        $this->roleService->restore($id);
        return ResponseHelper::success([], __('common.restored'));
    }

    /**
     * Restore bulk item deleted resource
     */
    public function bulkRestore(BulkActionRequest $request)
    {
        $this->roleService->bulkRestore($request->validated('ids'));
        return ResponseHelper::success([], __('common.restored'));
    }

    /**
     * Remove permanently the specified resource from storage.
     */
    public function forceDelete($id)
    {
        $role = Role::withTrashed()->findOrFail($id);
        $this->authorizeRoleAccess($role);
        if (auth()->user()->roles->contains('id', (int) $id)) {
            return ResponseHelper::error(__('role.cannot_delete_own_role'), 403);
        }
        $this->roleService->forceDelete($id);
        return ResponseHelper::success([], __('common.permanent_deleted'));
    }

    /**
     * Remove permanently bulk resource from storage.
     */
    public function bulkForceDelete(BulkActionRequest $request)
    {
        $userRoleIds = auth()->user()->roles->pluck('id')->toArray();
        $ids = $this->filterAccessibleRoleIds(
            array_diff($request->validated('ids'), $userRoleIds)
        );
        if (empty($ids)) {
            return ResponseHelper::error(__('role.cannot_delete_own_role'), 403);
        }
        $this->roleService->bulkForceDelete($ids);
        return ResponseHelper::success([], __('common.permanent_deleted'));
    }

    protected function filterAccessibleRoleIds(array $ids): array
    {
        $myLevel = auth()->user()->roleLevel();

        return Role::withTrashed()
            ->whereIn('id', $ids)
            ->where('level', '>=', $myLevel)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Get Role and Permission.
     */
    public function permission(Role $role)
    {
        $this->authorizeRoleAccess($role);
        $data = [
            'role' => $role,
        ];
        return view('admin.role.permission', $data);
    }

    /**
     * Update Role wise permission
     */
    public function updatePermission(Request $request, Role $role)
    {
        $this->authorizeRoleAccess($role);
        $this->roleService->permission($role, $request->all());

        activity('role')
            ->performedOn($role)
            ->event('permission_updated')
            ->withProperties(['permissions' => $request->input('permissions', [])])
            ->log("Permissions updated for role {$role->name}");

        return redirect()->route('roles.index')
            ->with('success', __('role.role_permission_updated'));
    }
}