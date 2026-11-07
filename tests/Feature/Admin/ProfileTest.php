<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class ProfileTest extends TestCase
{
    use DatabaseTransactions, CreatesAdminUser;

    public function test_guest_cannot_access_profile(): void
    {
        $this->get(route('profile.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_profile(): void
    {
        $this->createUserWithoutPermissions();

        $response = $this->actingAs($this->admin)->get(route('profile.index'));

        $response->assertStatus(200);
    }

    public function test_can_update_profile_name(): void
    {
        $this->createUserWithoutPermissions();

        $response = $this->actingAs($this->admin)->patch(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => $this->admin->email,
        ]);

        $response->assertRedirect();
    }

    public function test_can_update_password(): void
    {
        $this->createUserWithoutPermissions();

        $response = $this->actingAs($this->admin)->put(route('profile.password'), [
            'current_password' => 'password',
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        $response->assertRedirect();
    }

    public function test_password_update_requires_correct_current_password(): void
    {
        $this->createUserWithoutPermissions();

        $response = $this->actingAs($this->admin)->put(route('profile.password'), [
            'current_password' => 'wrongpassword',
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        $response->assertSessionHasErrors();
    }
}
