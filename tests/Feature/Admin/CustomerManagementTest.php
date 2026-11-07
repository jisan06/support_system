<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Repositories\Contracts\CustomerRepositoryInterface;
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
    protected CustomerRepositoryInterface $customerRepository;

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

        $this->customerRepository = app(CustomerRepositoryInterface::class);
    }

    /**
     * Test: CustomerRepository find($id) works without calling findOrFail on false.
     */
    public function test_customer_repository_find_returns_customer(): void
    {
        $found = $this->customerRepository->find($this->customer->id);

        $this->assertInstanceOf(User::class, $found);
        $this->assertEquals($this->customer->id, $found->id);
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
        ]);

        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('success');

        $this->customer->refresh();
        $this->assertEquals('Updated Customer Name', $this->customer->name);
        $this->assertEquals($originalHash, $this->customer->password);
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
}

