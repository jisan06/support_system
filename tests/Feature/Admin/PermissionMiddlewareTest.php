<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class PermissionMiddlewareTest extends TestCase
{
    use DatabaseTransactions, CreatesAdminUser;

    // ─── Users ───

    public function test_view_user_permission_required_for_users_index(): void
    {
        $this->createUserWithoutPermissions();

        $this->actingAs($this->admin)
            ->get(route('users.index'))
            ->assertStatus(403);
    }

    public function test_create_user_permission_required_for_users_create(): void
    {
        $this->createUserWithPermissions(['view_user']);

        $this->actingAs($this->admin)
            ->get(route('users.create'))
            ->assertStatus(403);
    }

    // ─── Roles ───

    public function test_view_role_permission_required_for_roles_index(): void
    {
        $this->createUserWithoutPermissions();

        $this->actingAs($this->admin)
            ->get(route('roles.index'))
            ->assertStatus(403);
    }

    public function test_edit_role_permission_required_for_permission_page(): void
    {
        $this->createUserWithPermissions(['view_role']);
        $role = \App\Models\Role::create(['name' => 'Test', 'status' => 'Active']);

        $this->actingAs($this->admin)
            ->get(route('roles.permission', $role))
            ->assertStatus(403);
    }

    // ─── Media ───

    public function test_view_media_permission_required(): void
    {
        $this->createUserWithoutPermissions();

        $this->actingAs($this->admin)
            ->get(route('media.index'))
            ->assertStatus(403);
    }

    // ─── Activity Logs ───

    public function test_view_activity_log_permission_required(): void
    {
        $this->createUserWithoutPermissions();

        $this->actingAs($this->admin)
            ->get(route('activity-logs.index'))
            ->assertStatus(403);
    }

    // ─── Settings (dynamic by group) ───

    public function test_view_setting_general_permission_required(): void
    {
        $this->createUserWithoutPermissions();

        $this->actingAs($this->admin)
            ->get(route('settings.index', 'general'))
            ->assertStatus(403);
    }

    public function test_edit_setting_general_permission_required_for_update(): void
    {
        $this->createUserWithPermissions(['view_setting_general']);

        $this->actingAs($this->admin)
            ->put(route('settings.update', 'general'), ['site_name' => 'Test'])
            ->assertStatus(403);
    }

    public function test_view_setting_seo_permission_required(): void
    {
        $this->createUserWithPermissions(['view_setting_general']);

        $this->actingAs($this->admin)
            ->get(route('settings.index', 'seo'))
            ->assertStatus(403);
    }

    // ─── Skip routes ───

    public function test_dashboard_does_not_require_permissions(): void
    {
        $this->createUserWithoutPermissions();

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertStatus(200);
    }

    public function test_profile_does_not_require_permissions(): void
    {
        $this->createUserWithoutPermissions();

        $this->actingAs($this->admin)
            ->get(route('profile.index'))
            ->assertStatus(200);
    }

    // ─── JSON 403 for AJAX ───

    public function test_permission_denied_returns_json_for_ajax(): void
    {
        $this->createUserWithoutPermissions();

        $response = $this->actingAs($this->admin)
            ->getJson(route('users.index'));

        $response->assertStatus(403);
        $response->assertJson(['success' => false]);
    }
}
