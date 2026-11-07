<?php

namespace Tests\Unit\Models;

use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use DatabaseTransactions;

    public function test_role_has_permissions_relationship(): void
    {
        $role = Role::create(['name' => 'Admin', 'status' => 'Active']);
        RolePermission::create(['role_id' => $role->id, 'slug' => 'view_user']);
        RolePermission::create(['role_id' => $role->id, 'slug' => 'create_user']);

        $this->assertCount(2, $role->permissions);
    }

    public function test_role_name_is_translatable(): void
    {
        $role = Role::create(['name' => ['en' => 'Admin', 'bn' => 'অ্যাডমিন'], 'status' => 'Active']);

        app()->setLocale('en');
        $this->assertEquals('Admin', $role->name);

        app()->setLocale('bn');
        $this->assertEquals('অ্যাডমিন', $role->name);
    }

    public function test_role_uses_soft_deletes(): void
    {
        $role = Role::create(['name' => 'Temp', 'status' => 'Active']);
        $role->delete();

        $this->assertSoftDeleted('roles', ['id' => $role->id]);
    }
}
