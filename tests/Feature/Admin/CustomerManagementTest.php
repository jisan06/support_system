<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class CustomerManagementTest extends TestCase
{
    use DatabaseTransactions, CreatesAdminUser;

    protected User $adminUser;
    protected User $customer;
    protected UserRepositoryInterface $userRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = $this->createAdminUser(['view_customer', 'create_customer', 'edit_customer', 'delete_customer']);

        $this->customer = User::create([
            'name'              => 'Test Customer',
            'email'             => 'test_cust_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('OriginalPassword123!'),
            'account_type'      => User::TYPE_CUSTOMER,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);

        $this->userRepository = app(UserRepositoryInterface::class);
    }

    /**
     * Test: Consolidated UserRepository findCustomer and findAdmin enforce account-type isolation.
     */
    public function test_shared_repository_enforces_account_type_isolation(): void
    {
        $foundCustomer = $this->userRepository->findCustomer($this->customer->id);
        $this->assertInstanceOf(User::class, $foundCustomer);
        $this->assertEquals($this->customer->id, $foundCustomer->id);

        $foundAdmin = $this->userRepository->findAdmin($this->adminUser->id);
        $this->assertInstanceOf(User::class, $foundAdmin);
        $this->assertEquals($this->adminUser->id, $foundAdmin->id);

        // Attempting to find an admin via findCustomer must fail
        $this->expectException(ModelNotFoundException::class);
        $this->userRepository->findCustomer($this->adminUser->id);
    }

    /**
     * Test: Attempting to find a customer via findAdmin must fail.
     */
    public function test_shared_repository_find_admin_rejects_customer(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $this->userRepository->findAdmin($this->customer->id);
    }

    /**
     * Test: Admin can view customer listing.
     */
    public function test_admin_can_view_customers_index(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('customers.index'));

        $response->assertStatus(200);
    }

    /**
     * Test: Admin can view customer edit page.
     */
    public function test_admin_can_view_customer_edit_page(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('customers.edit', $this->customer->id));

        $response->assertStatus(200);
        $response->assertSee($this->customer->email);
    }

    /**
     * Test: Admin can create customer through consolidated service, strictly enforcing account_type = customer.
     */
    public function test_admin_can_create_customer(): void
    {
        $email = 'new_customer_' . Str::random(8) . '@example.com';

        $response = $this->actingAs($this->adminUser)->post(route('customers.store'), [
            'name'             => 'Brand New Customer',
            'email'            => $email,
            'status'           => 'Active',
            'password'         => 'Password123!',
            'password_confirm' => 'Password123!',
            'role_ids'         => [1], // Even if submitted, roles must never be attached to customer
        ]);

        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('success');

        $created = User::where('email', $email)->first();
        $this->assertNotNull($created);
        $this->assertTrue($created->isCustomer());
        $this->assertCount(0, $created->roles);
    }

    /**
     * Test: Admin can update customer with blank password (password is optional on edit).
     */
    public function test_admin_can_update_customer_with_blank_password(): void
    {
        $originalHash = $this->customer->password;

        $response = $this->actingAs($this->adminUser)->patch(route('customers.update', $this->customer->id), [
            'name'             => 'Updated Customer Name',
            'email'            => $this->customer->email,
            'status'           => 'Active',
            'password'         => '',
            'password_confirm' => '',
            'account_type'     => User::TYPE_ADMIN, // Tampering attempt must be rejected/ignored
        ]);

        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('success');

        $this->customer->refresh();
        $this->assertEquals('Updated Customer Name', $this->customer->name);
        $this->assertEquals($originalHash, $this->customer->password);
        $this->assertTrue($this->customer->isCustomer()); // Account type preserved
    }

    /**
     * Test: Admin can update customer with a new password.
     */
    public function test_admin_can_update_customer_with_new_password(): void
    {
        $response = $this->actingAs($this->adminUser)->patch(route('customers.update', $this->customer->id), [
            'name'             => 'Customer Password Changed',
            'email'            => $this->customer->email,
            'status'           => 'Active',
            'password'         => 'NewSecretPassword123!',
            'password_confirm' => 'NewSecretPassword123!',
        ]);

        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('success');

        $this->customer->refresh();
        $this->assertTrue(Hash::check('NewSecretPassword123!', $this->customer->password));
    }

    /**
     * Test: Admin can delete and restore customer.
     */
    public function test_admin_can_delete_and_restore_customer(): void
    {
        // Delete
        $deleteResponse = $this->actingAs($this->adminUser)->delete(route('customers.destroy', $this->customer->id));
        $deleteResponse->assertStatus(200);
        $this->assertSoftDeleted('users', ['id' => $this->customer->id]);

        // Restore
        $restoreResponse = $this->actingAs($this->adminUser)->post(route('customers.restore', $this->customer->id));
        $restoreResponse->assertStatus(200);
        $this->assertNotSoftDeleted('users', ['id' => $this->customer->id]);
    }
}


