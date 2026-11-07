<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class DashboardTest extends TestCase
{
    use DatabaseTransactions, CreatesAdminUser;

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $this->createAllPermissionsAdmin();

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
    }

    public function test_dashboard_accessible_without_any_permissions(): void
    {
        $this->createUserWithoutPermissions();

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
    }
}
