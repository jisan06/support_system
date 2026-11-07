<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class CustomerProfileManagementTest extends TestCase
{
    use DatabaseTransactions, CreatesAdminUser;

    protected User $customer;
    protected User $otherCustomer;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::create([
            'name'              => 'Jane Doe',
            'email'             => 'jane_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_CUSTOMER,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);

        $this->otherCustomer = User::create([
            'name'              => 'Bob Smith',
            'email'             => 'bob_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_CUSTOMER,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);

        $this->adminUser = $this->createAdminUser(['view_ticket', 'create_ticket']);
    }

    /**
     * Test 1: Guests cannot access customer profile page.
     */
    public function test_guest_cannot_access_customer_profile(): void
    {
        $response = $this->get('/customer/profile');
        $response->assertRedirect('/login');

        $patchResponse = $this->patch('/customer/profile', [
            'name'  => 'New Name',
            'email' => 'newemail@example.com',
        ]);
        $patchResponse->assertRedirect('/login');
    }

    /**
     * Test 2: Admin user cannot access customer profile endpoint.
     */
    public function test_admin_user_cannot_access_customer_profile(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/customer/profile');
        // Customer middleware restricts access to customer accounts
        $this->assertTrue(in_array($response->status(), [302, 403]));
    }

    /**
     * Test 3: Customer can view their profile page.
     */
    public function test_customer_can_view_profile_page(): void
    {
        $response = $this->actingAs($this->customer)->get('/customer/profile');

        $response->assertOk();
        $response->assertViewIs('customer.profile.edit');
        $response->assertSee('Jane Doe');
        $response->assertSee($this->customer->email);
        $response->assertSee('Profile Settings');
    }

    /**
     * Test 4: Customer can update profile name and email successfully.
     */
    public function test_customer_can_update_profile_name_and_email(): void
    {
        $newEmail = 'jane_updated_' . Str::random(6) . '@example.com';

        $response = $this->actingAs($this->customer)
            ->patch('/customer/profile', [
                'name'  => 'Jane Updated',
                'email' => $newEmail,
            ]);

        $response->assertRedirect(route('customer.profile.edit'));
        $response->assertSessionHas('success');

        $this->customer->refresh();
        $this->assertEquals('Jane Updated', $this->customer->name);
        $this->assertEquals($newEmail, $this->customer->email);
    }

    /**
     * Test 5: Customer can update profile while keeping their existing email.
     */
    public function test_customer_can_update_profile_keeping_same_email(): void
    {
        $response = $this->actingAs($this->customer)
            ->patch('/customer/profile', [
                'name'  => 'Jane Same Email',
                'email' => $this->customer->email,
            ]);

        $response->assertRedirect(route('customer.profile.edit'));
        $response->assertSessionHas('success');

        $this->customer->refresh();
        $this->assertEquals('Jane Same Email', $this->customer->name);
    }

    /**
     * Test 6: Customer cannot update email to another user's email.
     */
    public function test_customer_cannot_update_email_to_another_users_email(): void
    {
        $response = $this->actingAs($this->customer)
            ->patch('/customer/profile', [
                'name'  => 'Jane Doe',
                'email' => $this->otherCustomer->email,
            ]);

        $response->assertSessionHasErrors('email');

        $this->customer->refresh();
        $this->assertNotEquals($this->otherCustomer->email, $this->customer->email);
    }

    /**
     * Test 7: Customer profile update validates required fields.
     */
    public function test_customer_profile_update_validates_required_fields(): void
    {
        $response = $this->actingAs($this->customer)
            ->patch('/customer/profile', [
                'name'  => '',
                'email' => 'invalid-email-format',
            ]);

        $response->assertSessionHasErrors(['name', 'email']);
    }

    /**
     * Test 8: Customer can update their password when providing correct current password.
     */
    public function test_customer_can_update_password_with_correct_current_password(): void
    {
        $response = $this->actingAs($this->customer)
            ->patch('/customer/profile', [
                'name'                  => $this->customer->name,
                'email'                 => $this->customer->email,
                'current_password'      => 'password123',
                'password'              => 'newsecurepass123',
                'password_confirmation' => 'newsecurepass123',
            ]);

        $response->assertRedirect(route('customer.profile.edit'));
        $response->assertSessionHas('success');

        $this->customer->refresh();
        $this->assertTrue(Hash::check('newsecurepass123', $this->customer->password));
    }

    /**
     * Test 9: Customer cannot update password when providing incorrect current password.
     */
    public function test_customer_cannot_update_password_with_incorrect_current_password(): void
    {
        $response = $this->actingAs($this->customer)
            ->patch('/customer/profile', [
                'name'                  => $this->customer->name,
                'email'                 => $this->customer->email,
                'current_password'      => 'wrongpassword',
                'password'              => 'newsecurepass123',
                'password_confirmation' => 'newsecurepass123',
            ]);

        $response->assertSessionHasErrors('current_password');

        $this->customer->refresh();
        $this->assertTrue(Hash::check('password123', $this->customer->password));
    }

    /**
     * Test 10: Customer cannot update password without matching confirmation.
     */
    public function test_customer_cannot_update_password_without_matching_confirmation(): void
    {
        $response = $this->actingAs($this->customer)
            ->patch('/customer/profile', [
                'name'                  => $this->customer->name,
                'email'                 => $this->customer->email,
                'current_password'      => 'password123',
                'password'              => 'newsecurepass123',
                'password_confirmation' => 'mismatchedpass999',
            ]);

        $response->assertSessionHasErrors('password');
    }

    /**
     * Test 11: Customer can upload and update avatar photo.
     */
    public function test_customer_can_upload_avatar(): void
    {
        Storage::fake('public');

        $avatarFile = UploadedFile::fake()->image('my_avatar.png', 200, 200);

        $response = $this->actingAs($this->customer)
            ->patch('/customer/profile', [
                'name'   => $this->customer->name,
                'email'  => $this->customer->email,
                'avatar' => $avatarFile,
            ]);

        $response->assertRedirect(route('customer.profile.edit'));
        $response->assertSessionHas('success');

        $this->customer->refresh();
        $this->assertNotNull($this->customer->getFirstMediaUrl('avatar'));
    }

    /**
     * Test 12: Customer can remove their avatar photo.
     */
    public function test_customer_can_remove_avatar(): void
    {
        Storage::fake('public');

        // First upload an avatar
        $avatarFile = UploadedFile::fake()->image('my_avatar.png', 200, 200);
        $this->actingAs($this->customer)
            ->patch('/customer/profile', [
                'name'   => $this->customer->name,
                'email'  => $this->customer->email,
                'avatar' => $avatarFile,
            ]);

        $this->customer->refresh();
        $this->assertNotEmpty($this->customer->mediaByCollection('avatar')->get());

        // Now remove it
        $removeResponse = $this->actingAs($this->customer)
            ->patch('/customer/profile', [
                'name'          => $this->customer->name,
                'email'         => $this->customer->email,
                'remove_avatar' => 1,
            ]);

        $removeResponse->assertRedirect(route('customer.profile.edit'));
        $removeResponse->assertSessionHas('success');

        $this->customer->refresh();
        $this->assertEmpty($this->customer->mediaByCollection('avatar')->get());
    }

    /**
     * Test 13: Customer cannot tamper with account_type, status, roles, or permissions.
     */
    public function test_customer_cannot_escalate_account_type_status_or_roles(): void
    {
        $role = Role::first();
        $roleId = $role ? $role->id : 1;

        $response = $this->actingAs($this->customer)
            ->patch('/customer/profile', [
                'name'         => 'Jane Tamper',
                'email'        => $this->customer->email,
                'account_type' => User::TYPE_ADMIN,
                'status'       => 'Inactive',
                'roles'        => [$roleId],
                'role_ids'     => [$roleId],
                'permissions'  => ['*'],
            ]);

        $response->assertRedirect(route('customer.profile.edit'));

        $this->customer->refresh();
        $this->assertEquals(User::TYPE_CUSTOMER, $this->customer->account_type->value);
        $this->assertEquals('Active', $this->customer->status);
        $this->assertFalse($this->customer->roles()->where('roles.id', $roleId)->exists());
    }

    /**
     * Test 14: Customer only updates their own profile even if injecting other user ID.
     */
    public function test_customer_only_updates_their_own_account_even_if_tampering_id(): void
    {
        $originalOtherName = $this->otherCustomer->name;
        $originalOtherEmail = $this->otherCustomer->email;

        $response = $this->actingAs($this->customer)
            ->patch('/customer/profile', [
                'id'      => $this->otherCustomer->id,
                'user_id' => $this->otherCustomer->id,
                'name'    => 'Hacked Other Name',
                'email'   => 'hacked_other_' . Str::random(5) . '@example.com',
            ]);

        $response->assertRedirect(route('customer.profile.edit'));

        // The authenticated customer is updated
        $this->customer->refresh();
        $this->assertEquals('Hacked Other Name', $this->customer->name);

        // The other customer is completely untouched
        $this->otherCustomer->refresh();
        $this->assertEquals($originalOtherName, $this->otherCustomer->name);
        $this->assertEquals($originalOtherEmail, $this->otherCustomer->email);
    }
}
