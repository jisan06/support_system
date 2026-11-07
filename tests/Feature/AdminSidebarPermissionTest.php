<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class AdminSidebarPermissionTest extends TestCase
{
    use DatabaseTransactions, CreatesAdminUser;

    /**
     * Test: A role without settings permission does not see Settings or Menu Order in sidebar.
     */
    public function test_role_without_settings_permission_does_not_see_settings_or_menu_order(): void
    {
        // Admin with only view_ticket permission
        $user = $this->createAdminUser(['view_ticket']);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertDontSee('menu-sort');
        $response->assertDontSee(route('menu-sort.index'));

        // Direct access to menu-sort route is also forbidden
        $accessResponse = $this->actingAs($user)->get(route('menu-sort.index'));
        $accessResponse->assertForbidden();
    }

    /**
     * Test: A role with edit_setting_general permission can see and access Menu Order.
     */
    public function test_role_with_settings_permission_can_see_and_access_menu_order(): void
    {
        $user = $this->createAdminUser(['edit_setting_general']);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee(route('menu-sort.index'));

        // Direct access to menu-sort is allowed
        $accessResponse = $this->actingAs($user)->get(route('menu-sort.index'));
        $accessResponse->assertOk();
    }
}

