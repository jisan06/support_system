<?php

namespace App\ViewModels\Admin;

use App\Models\User;
use App\Services\RoleService;
use Illuminate\Support\Collection;

class UserFormData
{
    public function __construct(
        public readonly Collection $roles,
        public readonly array $selectedRoles = [],
        public readonly ?User $user = null,
    ) {}

    public static function forCreate(RoleService $roleService): self
    {
        return new self(
            roles: $roleService->getActiveRoles(),
        );
    }

    public static function forEdit(User $user, RoleService $roleService): self
    {
        return new self(
            roles: $roleService->getActiveRoles(),
            selectedRoles: $user->roles()->pluck('roles.id')->toArray(),
            user: $user,
        );
    }

    public function toArray(): array
    {
        return [
            'roles' => $this->roles,
            'selected_roles' => $this->selectedRoles,
            'user' => $this->user,
        ];
    }
}
