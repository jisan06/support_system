<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class CustomerPortalTest extends TestCase
{
    use DatabaseTransactions, CreatesAdminUser;

    protected User $customer;
    protected User $otherCustomer;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::create([
            'name'              => 'Portal Customer One',
            'email'             => 'portal_cust1_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_CUSTOMER,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);

        $this->otherCustomer = User::create([
            'name'              => 'Portal Customer Two',
            'email'             => 'portal_cust2_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_CUSTOMER,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);

        $this->adminUser = $this->createAdminUser(['view_ticket', 'create_ticket']);
    }

    /**
     * Test 1: Unauthenticated guests cannot access the customer portal and are redirected to login.
     */
    public function test_unauthenticated_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('customer.dashboard'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test 2: Authenticated customer can access the customer portal dashboard.
     */
    public function test_authenticated_customer_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->customer)->get(route('customer.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Portal Customer One');
        $response->assertSee('Dashboard');
        $response->assertSee('Total Tickets');
        $response->assertSee('Open Tickets');
        $response->assertSee('Resolved Tickets');
        $response->assertSee('Logout');
    }

    /**
     * Test 3: Admin / internal staff are forbidden (403) from accessing the customer portal.
     */
    public function test_admin_user_is_forbidden_from_customer_portal(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('customer.dashboard'));

        $response->assertStatus(403);
    }

    /**
     * Test 4: Dashboard displays ticket counts strictly for the authenticated customer.
     */
    public function test_dashboard_shows_only_authenticated_customer_ticket_counts(): void
    {
        // Customer 1: 2 Open, 1 Resolved = 3 Total
        Ticket::create([
            'ticket_number' => 'TCK-C1-001',
            'customer_id'   => $this->customer->id,
            'subject'       => 'Customer 1 Issue 1',
            'status'        => 'open',
            'priority'      => 'medium',
        ]);
        Ticket::create([
            'ticket_number' => 'TCK-C1-002',
            'customer_id'   => $this->customer->id,
            'subject'       => 'Customer 1 Issue 2',
            'status'        => 'open',
            'priority'      => 'low',
        ]);
        Ticket::create([
            'ticket_number' => 'TCK-C1-003',
            'customer_id'   => $this->customer->id,
            'subject'       => 'Customer 1 Issue 3',
            'status'        => 'resolved',
            'priority'      => 'high',
        ]);

        // Customer 2: 4 Open, 1 Resolved = 5 Total
        for ($i = 1; $i <= 4; $i++) {
            Ticket::create([
                'ticket_number' => 'TCK-C2-00' . $i,
                'customer_id'   => $this->otherCustomer->id,
                'subject'       => 'Customer 2 Issue ' . $i,
                'status'        => 'open',
                'priority'      => 'medium',
            ]);
        }
        Ticket::create([
            'ticket_number' => 'TCK-C2-005',
            'customer_id'   => $this->otherCustomer->id,
            'subject'       => 'Customer 2 Issue 5',
            'status'        => 'resolved',
            'priority'      => 'medium',
        ]);

        // Customer 1 view
        $response1 = $this->actingAs($this->customer)->get(route('customer.dashboard'));
        $response1->assertStatus(200);
        $response1->assertViewHas('counts', [
            'total'       => 3,
            'open'        => 2,
            'in_progress' => 0,
            'resolved'    => 1,
            'closed'      => 0,
        ]);

        // Customer 2 view
        $response2 = $this->actingAs($this->otherCustomer)->get(route('customer.dashboard'));
        $response2->assertStatus(200);
        $response2->assertViewHas('counts', [
            'total'       => 5,
            'open'        => 4,
            'in_progress' => 0,
            'resolved'    => 1,
            'closed'      => 0,
        ]);
    }

    /**
     * Test 5: Customer account cannot access admin panel routes.
     */
    public function test_customer_cannot_access_admin_routes(): void
    {
        $response = $this->actingAs($this->customer)->get(route('admin.dashboard'));

        // CheckPermission middleware blocks non-admins
        $this->assertTrue(in_array($response->status(), [403, 302]));

        $ticketCreateResponse = $this->actingAs($this->customer)->get(route('tickets.create'));
        $this->assertTrue(in_array($ticketCreateResponse->status(), [403, 302]));
    }

    /**
     * Test 6: Admin user can still access admin dashboard normally.
     */
    public function test_admin_can_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.dashboard'));

        $response->assertStatus(200);
    }

    /**
     * Test 7: Login redirect redirects customers to customer.dashboard and admins to admin.dashboard.
     */
    public function test_login_redirection_by_account_type(): void
    {
        $customerLogin = $this->post(route('login'), [
            'email'    => $this->customer->email,
            'password' => 'password123',
        ]);
        $customerLogin->assertRedirect(route('customer.dashboard', absolute: false));

        // Logout
        $this->post(route('logout'));

        $adminLogin = $this->post(route('login'), [
            'email'    => $this->adminUser->email,
            'password' => 'password', // Default factory admin password
        ]);
        $adminLogin->assertRedirect(route('admin.dashboard', absolute: false));
    }

    /**
     * Test 8: Guest user visiting root URL (/) redirects to login page.
     */
    public function test_root_url_redirects_guest_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    /**
     * Test 9: Authenticated customer visiting root URL (/) redirects to Customer Dashboard (never /admin).
     */
    public function test_root_url_redirects_customer_to_customer_dashboard(): void
    {
        $response = $this->actingAs($this->customer)->get('/');

        $response->assertRedirect(route('customer.dashboard'));
        $this->assertStringNotContainsString('/admin', $response->headers->get('Location'));
    }

    /**
     * Test 10: Authenticated admin visiting root URL (/) redirects to Admin Dashboard.
     */
    public function test_root_url_redirects_admin_to_admin_dashboard(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/');

        $response->assertRedirect(route('admin.dashboard'));
    }

    /**
     * Test 11: Common login URL structure and legacy redirect.
     */
    public function test_common_login_url_structure(): void
    {
        // Named route 'login' is /login
        $this->assertEquals(url('/login'), route('login'));

        // Guest can render login screen at /login
        $response = $this->get('/login');
        $response->assertStatus(200);

        // Legacy /admin/login redirects to /login
        $legacy = $this->get('/admin/login');
        $legacy->assertRedirect(route('login'));
    }

    /**
     * Test 12: Customer dashboard URL structure is /customer/dashboard.
     */
    public function test_customer_dashboard_url_structure(): void
    {
        $this->assertEquals(url('/customer/dashboard'), route('customer.dashboard'));

        $response = $this->actingAs($this->customer)->get('/customer/dashboard');
        $response->assertStatus(200);
    }

    /**
     * Test 13: Logout redirects to /login.
     */
    public function test_logout_redirects_to_login(): void
    {
        $response = $this->actingAs($this->customer)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }
}

