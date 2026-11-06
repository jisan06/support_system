<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class UserManagementTest extends TestCase
{
    use RefreshDatabase, CreatesAdminUser;

    // ─── Access Control ───

    public function test_guest_cannot_access_users(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_access_users(): void
    {
        $this->createUserWithoutPermissions();

        $response = $this->actingAs($this->admin)->get(route('users.index'));

        $response->assertStatus(403);
    }

    public function test_user_with_permission_can_access_users_index(): void
    {
        $this->createUserWithPermissions(['view_user']);

        $response = $this->actingAs($this->admin)->get(route('users.index'));

        $response->assertStatus(200);
    }

    // ─── Create ───

    public function test_user_with_permission_can_access_create_form(): void
    {
        $this->createUserWithPermissions(['view_user', 'create_user']);

        $response = $this->actingAs($this->admin)->get(route('users.create'));

        $response->assertStatus(200);
    }

    public function test_user_without_create_permission_cannot_access_create_form(): void
    {
        $this->createUserWithPermissions(['view_user']);

        $response = $this->actingAs($this->admin)->get(route('users.create'));

        $response->assertStatus(403);
    }

    public function test_can_store_user_with_valid_data(): void
    {
        $this->createAllPermissionsAdmin();

        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'status' => 'Active',
            'password' => 'Password1!',
            'password_confirm' => 'Password1!',
            'role_ids' => [$this->adminRole->id],
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
    }

    public function test_store_user_requires_name(): void
    {
        $this->createAllPermissionsAdmin();

        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'email' => 'test@example.com',
            'status' => 'Active',
            'role_ids' => [$this->adminRole->id],
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_store_user_requires_valid_email(): void
    {
        $this->createAllPermissionsAdmin();

        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'status' => 'Active',
            'role_ids' => [$this->adminRole->id],
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_store_user_requires_unique_email(): void
    {
        $this->createAllPermissionsAdmin();

        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Duplicate User',
            'email' => $this->admin->email,
            'status' => 'Active',
            'role_ids' => [$this->adminRole->id],
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_password_confirmation_must_match(): void
    {
        $this->createAllPermissionsAdmin();

        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'status' => 'Active',
            'password' => 'Password1!',
            'password_confirm' => 'Different1!',
            'role_ids' => [$this->adminRole->id],
        ]);

        $response->assertSessionHasErrors('password_confirm');
    }

    // ─── Edit / Update ───

    public function test_can_access_edit_form(): void
    {
        $this->createAllPermissionsAdmin();
        $user = User::factory()->create(['status' => 'Active']);

        $response = $this->actingAs($this->admin)->get(route('users.edit', $user));

        $response->assertStatus(200);
    }

    public function test_can_update_user(): void
    {
        $this->createAllPermissionsAdmin();
        $user = User::factory()->create(['status' => 'Active']);
        $user->roles()->attach($this->adminRole->id, ['created_by' => $this->admin->id]);

        $response = $this->actingAs($this->admin)->put(route('users.update', $user), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'status' => 'Active',
            'role_ids' => [$this->adminRole->id],
        ]);

        $response->assertRedirect(route('users.index'));
    }

    // ─── Delete (returns JSON via ResponseHelper) ───

    public function test_can_soft_delete_user(): void
    {
        $this->createAllPermissionsAdmin();
        $user = User::factory()->create(['status' => 'Active']);

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('users.destroy', $user));

        $response->assertOk();
        $response->assertJson(['status' => 'success']);
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_user_without_delete_permission_cannot_delete(): void
    {
        $this->createUserWithPermissions(['view_user', 'create_user', 'edit_user']);
        $user = User::factory()->create(['status' => 'Active']);

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('users.destroy', $user));

        $response->assertStatus(403);
    }

    // ─── Trash ───

    public function test_can_access_trash(): void
    {
        $this->createUserWithPermissions(['view_user']);

        $response = $this->actingAs($this->admin)->get(route('users.trash'));

        $response->assertStatus(200);
    }

    // ─── Restore (returns JSON via ResponseHelper) ───

    public function test_can_restore_soft_deleted_user(): void
    {
        $this->createAllPermissionsAdmin();
        $user = User::factory()->create(['status' => 'Active']);
        $user->delete();

        $response = $this->actingAs($this->admin)
            ->postJson(route('users.restore', $user->id));

        $response->assertOk();
        $response->assertJson(['status' => 'success']);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'deleted_at' => null]);
    }
}
