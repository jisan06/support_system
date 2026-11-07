<?php

namespace Tests\Unit\Models;

use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_has_roles_relationship(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Admin', 'status' => 'Active']);
        $user->roles()->attach($role->id, ['created_by' => $user->id]);

        $this->assertTrue($user->roles->contains($role));
    }

    public function test_has_permission_to_returns_true_when_user_has_permission(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Admin', 'status' => 'Active']);
        RolePermission::create(['role_id' => $role->id, 'slug' => 'view_user']);
        $user->roles()->attach($role->id, ['created_by' => $user->id]);

        $this->assertTrue($user->hasPermissionTo('view_user'));
    }

    public function test_has_permission_to_returns_false_when_user_lacks_permission(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Viewer', 'status' => 'Active']);
        $user->roles()->attach($role->id, ['created_by' => $user->id]);

        $this->assertFalse($user->hasPermissionTo('delete_user'));
    }

    public function test_user_without_roles_has_no_permissions(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->hasPermissionTo('view_user'));
    }

    public function test_user_name_is_translatable(): void
    {
        $user = User::factory()->create(['name' => ['en' => 'John', 'bn' => 'জন']]);

        app()->setLocale('en');
        $this->assertEquals('John', $user->name);

        app()->setLocale('bn');
        $this->assertEquals('জন', $user->name);
    }

    public function test_user_uses_soft_deletes(): void
    {
        $user = User::factory()->create();
        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertNotNull(User::withTrashed()->find($user->id));
    }

    public function test_created_by_is_set_when_authenticated(): void
    {
        $creator = User::factory()->create();
        $this->actingAs($creator);

        $user = User::factory()->create();

        $this->assertEquals($creator->id, $user->created_by);
    }
}
