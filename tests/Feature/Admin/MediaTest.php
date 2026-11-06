<?php

namespace Tests\Feature\Admin;

use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class MediaTest extends TestCase
{
    use RefreshDatabase, CreatesAdminUser;

    // ─── Access Control ───

    public function test_guest_cannot_access_media(): void
    {
        $this->get(route('media.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_access_media(): void
    {
        $this->createUserWithoutPermissions();

        $response = $this->actingAs($this->admin)->get(route('media.index'));

        $response->assertStatus(403);
    }

    public function test_user_with_permission_can_access_media_index(): void
    {
        $this->createUserWithPermissions(['view_media']);

        $response = $this->actingAs($this->admin)->get(route('media.index'));

        $response->assertStatus(200);
    }

    // ─── Upload ───

    public function test_can_upload_image(): void
    {
        Storage::fake('public');
        $this->createAllPermissionsAdmin();

        $response = $this->actingAs($this->admin)
            ->postJson(route('media.store'), [
                'file' => UploadedFile::fake()->image('test-logo.png', 200, 200),
            ]);

        $response->assertOk();
        $response->assertJson(['status' => 'success']);
        $response->assertJsonPath('data.file_name', 'test-logo.png');
    }

    public function test_user_without_create_permission_cannot_upload(): void
    {
        $this->createUserWithPermissions(['view_media']);

        $response = $this->actingAs($this->admin)
            ->postJson(route('media.store'), [
                'file' => UploadedFile::fake()->image('test.png'),
            ]);

        $response->assertStatus(403);
    }

    // ─── Delete (returns JSON via ResponseHelper) ───

    public function test_can_delete_media(): void
    {
        Storage::fake('public');
        $this->createAllPermissionsAdmin();

        $media = Media::create([
            'mediable_type' => Media::class,
            'mediable_id' => 0,
            'collection' => 'default',
            'file_name' => 'test.png',
            'disk' => 'public',
            'path' => 'media/test.png',
            'mime_type' => 'image/png',
            'size' => 1024,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('media.destroy', $media));

        $response->assertOk();
        $response->assertJson(['status' => 'success']);
    }

    public function test_user_without_delete_permission_cannot_delete_media(): void
    {
        $this->createUserWithPermissions(['view_media', 'create_media']);

        $media = Media::create([
            'mediable_type' => Media::class,
            'mediable_id' => 0,
            'collection' => 'default',
            'file_name' => 'test.png',
            'disk' => 'public',
            'path' => 'media/test.png',
            'mime_type' => 'image/png',
            'size' => 1024,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('media.destroy', $media));

        $response->assertStatus(403);
    }

    // ─── AJAX Pagination ───

    public function test_media_index_returns_json_for_ajax(): void
    {
        $this->createUserWithPermissions(['view_media']);

        $response = $this->actingAs($this->admin)
            ->get(route('media.index'), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
    }
}
