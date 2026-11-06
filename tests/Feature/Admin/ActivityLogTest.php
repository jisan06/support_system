<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase, CreatesAdminUser;

    // ─── Access Control ───

    public function test_guest_cannot_access_activity_logs(): void
    {
        $this->get(route('activity-logs.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_access_activity_logs(): void
    {
        $this->createUserWithoutPermissions();

        $response = $this->actingAs($this->admin)->get(route('activity-logs.index'));

        $response->assertStatus(403);
    }

    public function test_user_with_permission_can_access_activity_logs(): void
    {
        $this->createUserWithPermissions(['view_activity_log']);

        $response = $this->actingAs($this->admin)->get(route('activity-logs.index'));

        $response->assertStatus(200);
    }

    // ─── Show ───

    public function test_can_view_activity_log_detail(): void
    {
        $this->createUserWithPermissions(['view_activity_log']);

        $activity = activity('test')->log('Test activity');

        $response = $this->actingAs($this->admin)->get(route('activity-logs.show', $activity->id));

        $response->assertStatus(200);
    }

    // ─── Delete (returns JSON via ResponseHelper) ───

    public function test_can_delete_activity_log(): void
    {
        $this->createAllPermissionsAdmin();

        $activity = activity('test')->log('Test activity');

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('activity-logs.destroy', $activity->id));

        $response->assertOk();
        $response->assertJson(['status' => 'success']);
    }

    public function test_user_without_delete_permission_cannot_delete(): void
    {
        $this->createUserWithPermissions(['view_activity_log']);

        $activity = activity('test')->log('Test activity');

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('activity-logs.destroy', $activity->id));

        $response->assertStatus(403);
    }
}
