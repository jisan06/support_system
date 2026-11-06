<?php

namespace App\ViewModels\Admin;

use App\Models\Role;

class RoleFormData
{
    public function __construct(
        public readonly ?Role $role = null,
        public readonly int $minLevel = 1,
    ) {}

    public static function forCreate(): self
    {
        return new self(
            minLevel: auth()->user()->roleLevel(),
        );
    }

    public static function forEdit(Role $role): self
    {
        return new self(
            role: $role,
            minLevel: auth()->user()->roleLevel(),
        );
    }

    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'min_level' => $this->minLevel,
        ];
    }
}
