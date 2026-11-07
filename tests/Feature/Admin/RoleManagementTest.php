<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class RoleManagementTest extends TestCase
{
    use DatabaseTransactions, CreatesAdminUser;

    // ─── Access Control ───

    public function test_guest_cannot_access_roles(): void
    {
        $this->get(route('roles.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_access_roles(): void
    {
        $this->createUserWithoutPermissions();

        $response = $this->actingAs($this->admin)->get(route('roles.index'));

        $response->assertStatus(403);
    }

    public function test_user_with_permission_can_access_roles_index(): void
    {
        $this->createUserWithPermissions(['view_role']);

        $response = $this->actingAs($this->admin)->get(route('roles.index'));

        $response->assertStatus(200);
    }

    // ─── Create ───

    public function test_can_access_create_form(): void
    {
        $this->createUserWithPermissions(['view_role', 'create_role']);

        $response = $this->actingAs($this->admin)->get(route('roles.create'));

        $response->assertStatus(200);
    }

    public function test_can_store_role_with_valid_data(): void
    {
        $this->createAllPermissionsAdmin();

        $response = $this->actingAs($this->admin)->post(route('roles.store'), [
            'name' => 'Editor',
            'level' => 99,
            'status' => 'Active',
        ]);

        $response->assertRedirect(route('roles.index'));
        $this->assertDatabaseHas('roles', ['status' => 'Active']);
        $this->assertEquals(2, Role::count());
    }

    public function test_store_role_requires_name(): void
    {
        $this->createAllPermissionsAdmin();

        $response = $this->actingAs($this->admin)->post(route('roles.store'), [
            'status' => 'Active',
        ]);

        $response->assertSessionHasErrors('name');
    }

    // ─── Edit / Update ───

    public function test_can_update_role(): void
    {
        $this->createAllPermissionsAdmin();
        $role = Role::create(['name' => 'Editor', 'status' => 'Active']);

        $response = $this->actingAs($this->admin)->put(route('roles.update', $role), [
            'name' => 'Senior Editor',
            'level' => 99,
            'status' => 'Active',
        ]);

        $response->assertRedirect(route('roles.index'));
    }

    // ─── Delete (returns JSON via ResponseHelper) ───

    public function test_can_soft_delete_role(): void
    {
        $this->createAllPermissionsAdmin();
        $role = Role::create(['name' => 'Temporary', 'status' => 'Active']);

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('roles.destroy', $role));

        $response->assertOk();
        $response->assertJson(['status' => 'success']);
        $this->assertSoftDeleted('roles', ['id' => $role->id]);
    }

    public function test_cannot_delete_own_role(): void
    {
        $this->createAllPermissionsAdmin();

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('roles.destroy', $this->adminRole));

        $response->assertStatus(403);
        $response->assertJson(['status' => 'error']);
        $this->assertNotSoftDeleted('roles', ['id' => $this->adminRole->id]);
    }

    // ─── Trash / Restore ───

    public function test_can_access_trash(): void
    {
        $this->createUserWithPermissions(['view_role']);

        $response = $this->actingAs($this->admin)->get(route('roles.trash'));

        $response->assertStatus(200);
    }

    public function test_can_restore_soft_deleted_role(): void
    {
        $this->createAllPermissionsAdmin();
        $role = Role::create(['name' => 'Restore Me', 'status' => 'Active']);
        $role->delete();

        $response = $this->actingAs($this->admin)
            ->postJson(route('roles.restore', $role->id));

        $response->assertOk();
        $response->assertJson(['status' => 'success']);
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'deleted_at' => null]);
    }

    // ─── Permissions ───

    public function test_can_access_permission_page(): void
    {
        $this->createUserWithPermissions(['view_role', 'edit_role']);
        $role = Role::create(['name' => 'Editor', 'status' => 'Active']);

        $response = $this->actingAs($this->admin)->get(route('roles.permission', $role));

        $response->assertStatus(200);
    }

    public function test_can_update_role_permissions(): void
    {
        $this->createAllPermissionsAdmin();
        $role = Role::create(['name' => 'Editor', 'status' => 'Active']);

        $response = $this->actingAs($this->admin)->post(route('roles.permission_update', $role), [
            'permissions' => ['view_user', 'view_role'],
        ]);

        $response->assertRedirect(route('roles.index'));
        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $role->id,
            'slug' => 'view_user',
        ]);
        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $role->id,
            'slug' => 'view_role',
        ]);
    }
}
