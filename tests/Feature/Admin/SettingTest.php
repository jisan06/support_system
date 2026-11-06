<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class SettingTest extends TestCase
{
    use RefreshDatabase, CreatesAdminUser;

    // ─── Access Control ───

    public function test_guest_cannot_access_settings(): void
    {
        $this->get(route('settings.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_access_settings(): void
    {
        $this->createUserWithoutPermissions();

        $response = $this->actingAs($this->admin)->get(route('settings.index', 'general'));

        $response->assertStatus(403);
    }

    public function test_user_with_permission_can_access_general_settings(): void
    {
        $this->createUserWithPermissions(['view_setting_general']);

        $response = $this->actingAs($this->admin)->get(route('settings.index', 'general'));

        $response->assertStatus(200);
    }

    // ─── Settings Groups ───

    public function test_can_access_seo_settings(): void
    {
        $this->createUserWithPermissions(['view_setting_seo']);

        $response = $this->actingAs($this->admin)->get(route('settings.index', 'seo'));

        $response->assertStatus(200);
    }

    public function test_can_access_contact_settings(): void
    {
        $this->createUserWithPermissions(['view_setting_contact']);

        $response = $this->actingAs($this->admin)->get(route('settings.index', 'contact'));

        $response->assertStatus(200);
    }

    public function test_can_access_email_settings(): void
    {
        $this->createUserWithPermissions(['view_setting_email']);

        $response = $this->actingAs($this->admin)->get(route('settings.index', 'email'));

        $response->assertStatus(200);
    }

    public function test_invalid_settings_group_returns_404(): void
    {
        $this->createAllPermissionsAdmin();

        $response = $this->actingAs($this->admin)->get(route('settings.index', 'nonexistent'));

        $response->assertStatus(404);
    }

    // ─── Update Settings ───

    public function test_can_update_general_settings(): void
    {
        $this->createAllPermissionsAdmin();

        $response = $this->actingAs($this->admin)->put(route('settings.update', 'general'), [
            'site_name' => 'The Admin',
            'site_tagline' => 'Powerful Admin Panel',
            'admin_logo_variant' => 'dark',
            'auth_logo_variant' => 'dark',
        ]);

        $response->assertRedirect(route('settings.index', 'general'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('settings', [
            'group' => 'general',
            'key' => 'site_name',
            'value' => 'The Admin',
        ]);
    }

    public function test_can_update_seo_settings(): void
    {
        $this->createAllPermissionsAdmin();

        $response = $this->actingAs($this->admin)->put(route('settings.update', 'seo'), [
            'meta_title' => 'The Admin - Laravel Admin Panel',
            'meta_description' => 'A powerful admin panel built with Laravel.',
            'meta_keywords' => 'laravel, admin, panel',
        ]);

        $response->assertRedirect(route('settings.index', 'seo'));
        $this->assertDatabaseHas('settings', [
            'group' => 'seo',
            'key' => 'meta_title',
            'value' => 'The Admin - Laravel Admin Panel',
        ]);
    }

    public function test_can_update_contact_settings(): void
    {
        $this->createAllPermissionsAdmin();

        $response = $this->actingAs($this->admin)->put(route('settings.update', 'contact'), [
            'company_name' => 'The Admin Inc.',
            'primary_email' => 'info@theadmin.com',
            'phone' => '+1234567890',
        ]);

        $response->assertRedirect(route('settings.index', 'contact'));
    }

    public function test_can_update_email_settings(): void
    {
        $this->createAllPermissionsAdmin();

        $response = $this->actingAs($this->admin)->put(route('settings.update', 'email'), [
            'mail_mailer' => 'smtp',
            'mail_host' => 'smtp.gmail.com',
            'mail_port' => '587',
            'mail_encryption' => 'tls',
        ]);

        $response->assertRedirect(route('settings.index', 'email'));
    }

    public function test_user_without_edit_permission_cannot_update_settings(): void
    {
        $this->createUserWithPermissions(['view_setting_general']);

        $response = $this->actingAs($this->admin)->put(route('settings.update', 'general'), [
            'site_name' => 'Hacked',
        ]);

        $response->assertStatus(403);
    }

    public function test_update_nonexistent_group_returns_404(): void
    {
        $this->createAllPermissionsAdmin();

        $response = $this->actingAs($this->admin)->put(route('settings.update', 'nonexistent'), []);

        $response->assertStatus(404);
    }

    // ─── Auth Logo Variant Setting ───

    public function test_auth_logo_variant_defaults_to_dark(): void
    {
        $this->createAllPermissionsAdmin();

        $this->assertEquals('dark', setting('auth_logo_variant', 'dark'));
    }

    public function test_can_set_auth_logo_variant_to_light(): void
    {
        $this->createAllPermissionsAdmin();

        $this->actingAs($this->admin)->put(route('settings.update', 'general'), [
            'auth_logo_variant' => 'light',
        ]);

        $this->assertDatabaseHas('settings', [
            'group' => 'general',
            'key' => 'auth_logo_variant',
            'value' => 'light',
        ]);
    }

    public function test_can_set_auth_logo_variant_to_dark(): void
    {
        $this->createAllPermissionsAdmin();

        $this->actingAs($this->admin)->put(route('settings.update', 'general'), [
            'auth_logo_variant' => 'dark',
        ]);

        $this->assertDatabaseHas('settings', [
            'group' => 'general',
            'key' => 'auth_logo_variant',
            'value' => 'dark',
        ]);
    }

    // ─── Language Sync ───

    public function test_changing_default_language_syncs_session(): void
    {
        $this->createAllPermissionsAdmin();

        $response = $this->actingAs($this->admin)->put(route('settings.update', 'general'), [
            'default_language' => 'bn',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'bn');
    }
}
