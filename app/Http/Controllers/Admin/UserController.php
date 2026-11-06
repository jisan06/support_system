<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\UsersDataTable;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkActionRequest;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\RoleService;
use App\Services\UserService;
use App\ViewModels\Admin\UserFormData;

class UserController extends Controller
{
    protected UserService $service;
    protected RoleService $roleService;

    public function __construct(UserService $service, RoleService $roleService)
    {
        $this->service = $service;
        $this->roleService = $roleService;
    }

    protected function authorizeUserAccess(User $user): void
    {
        // Enforce account_type: User management strictly handles internal accounts
        if (!$user->isAdmin()) {
            abort(404, 'User not found in internal accounts.');
        }

        $user->loadMissing('roles');
        if ($user->roleLevel() < auth()->user()->roleLevel()) {
            abort(403);
        }
    }

    protected function isRootAdmin(User $user): bool
    {
        return $user->id === 1;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(UsersDataTable $dataTable)
    {
        $data = [
            'page_type' => 'index',
            'bulk_actions' => [
                (object)['value' => 'trash', 'label' => __('common.move_trash')]
            ],
            'bulk_delete_url' => route('users.bulk_delete'),
        ];
        return $dataTable->render('admin.user.index', $data);
    }

    /**
     * Display a listing of the deleted resource.
     */
    public function trash(UsersDataTable $dataTable)
    {
        $data = [
            'page_type' => 'trash',
            'bulk_actions' => [
                (object)['value' => 'restore', 'label' => __('common.restore')],
                (object)['value' => 'force_delete', 'label' => __('common.delete_permanently')],
            ],
            'bulk_restore_url' => route('users.bulk_restore'),
            'bulk_delete_url' => route('users.bulk_force_delete'),
        ];
        return $dataTable->render('admin.user.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.user.form',
            UserFormData::forCreate($this->roleService)->toArray()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request)
    {
        $data = $request->validated();
        $data['account_type'] = User::TYPE_ADMIN;

        $this->service->create($data);
        return redirect()->route('users.index')
            ->with('success', __('user.user_created'));
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $this->authorizeUserAccess($user);
        $user->load('roles');

        return view('admin.user.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $this->authorizeUserAccess($user);
        if ($this->isRootAdmin($user) && auth()->id() !== 1) {
            abort(403);
        }
        return view('admin.user.form',
            UserFormData::forEdit($user, $this->roleService)->toArray()
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, User $user)
    {
        $this->authorizeUserAccess($user);
        if ($this->isRootAdmin($user) && auth()->id() !== 1) {
            abort(403);
        }

        $data = $request->validated();
        unset($data['account_type']); // Prevent unauthorized account_type mutation

        $this->service->update($user->id, $data);
        return redirect()->route('users.index')
            ->with('success', __('user.user_updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $this->authorizeUserAccess($user);
        if ($this->isRootAdmin($user)) {
            return ResponseHelper::error(__('user.cannot_delete_root_admin'), 403);
        }
        $this->service->delete($user->id);
        return ResponseHelper::success([], __('common.deleted'));
    }

    /**
     * Remove bulk item from storage.
     */
    public function bulkDestroy(BulkActionRequest $request)
    {
        $ids = $this->filterAccessibleUserIds($request->validated('ids'));
        $this->service->bulkDelete($ids);
        return ResponseHelper::success([], __('common.deleted'));
    }

    /**
     * Restore the deleted resource
     */
    public function restore($id)
    {
        $this->service->restore($id);
        return ResponseHelper::success([], __('common.restored'));
    }

    /**
     * Restore bulk item deleted resource
     */
    public function bulkRestore(BulkActionRequest $request)
    {
        $ids = $this->filterAccessibleUserIds($request->validated('ids'));
        $this->service->bulkRestore($ids);
        return ResponseHelper::success([], __('common.restored'));
    }

    /**
     * Remove permanently the specified resource from storage.
     */
    public function forceDelete($id)
    {
        $this->service->forceDelete($id);
        return ResponseHelper::success([], __('common.permanent_deleted'));
    }

    /**
     * Remove permanently bulk resource from storage.
     */
    public function bulkForceDelete(BulkActionRequest $request)
    {
        $ids = $this->filterAccessibleUserIds($request->validated('ids'));
        $this->service->bulkForceDelete($ids);
        return ResponseHelper::success([], __('common.permanent_deleted'));
    }

    protected function filterAccessibleUserIds(array $ids): array
    {
        // Always protect root admin and restrict to admin account_type
        $ids = array_values(array_diff($ids, [1]));

        $myLevel = auth()->user()->roleLevel();
        $protectedIds = User::whereIn('id', $ids)
            ->where(function ($q) use ($myLevel) {
                $q->where('account_type', '!=', User::TYPE_ADMIN)
                  ->orWhereHas('roles', fn ($r) => $r->where('level', '<', $myLevel));
            })
            ->pluck('id')
            ->toArray();

        return array_values(array_diff($ids, $protectedIds));
    }
}
