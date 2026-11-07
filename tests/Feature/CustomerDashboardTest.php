<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\GeneralNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class CustomerDashboardTest extends TestCase
{
    use DatabaseTransactions, CreatesAdminUser;

    protected User $customer;
    protected User $otherCustomer;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::create([
            'name'              => 'Sarah Customer',
            'email'             => 'sarah_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_CUSTOMER,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);

        $this->otherCustomer = User::create([
            'name'              => 'David Other',
            'email'             => 'david_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_CUSTOMER,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);

        $this->adminUser = $this->createAdminUser(['view_ticket', 'create_ticket']);
    }

    /**
     * Test 1: Dashboard displays all five ticket summary counts accurately.
     */
    public function test_customer_dashboard_displays_all_five_ticket_summary_counts(): void
    {
        // Create tickets with various statuses for customer
        Ticket::create([
            'ticket_number' => 'TCK-' . Str::random(6),
            'customer_id'   => $this->customer->id,
            'subject'       => 'Issue 1 - Open',
            'status'        => 'open',
            'priority'      => 'high',
        ]);
        Ticket::create([
            'ticket_number' => 'TCK-' . Str::random(6),
            'customer_id'   => $this->customer->id,
            'subject'       => 'Issue 2 - In Progress',
            'status'        => 'in_progress',
            'priority'      => 'medium',
        ]);
        Ticket::create([
            'ticket_number' => 'TCK-' . Str::random(6),
            'customer_id'   => $this->customer->id,
            'subject'       => 'Issue 3 - Resolved',
            'status'        => 'resolved',
            'priority'      => 'low',
        ]);
        Ticket::create([
            'ticket_number' => 'TCK-' . Str::random(6),
            'customer_id'   => $this->customer->id,
            'subject'       => 'Issue 4 - Closed',
            'status'        => 'closed',
            'priority'      => 'low',
        ]);

        $response = $this->actingAs($this->customer)->get(route('customer.dashboard'));

        $response->assertOk();
        $response->assertViewHas('counts', [
            'total'       => 4,
            'open'        => 1,
            'in_progress' => 1,
            'resolved'    => 1,
            'closed'      => 1,
        ]);
    }

    /**
     * Test 2: Dashboard displays recent tickets with links to details.
     */
    public function test_customer_dashboard_displays_recent_tickets(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TCK-RECENT-999',
            'customer_id'   => $this->customer->id,
            'subject'       => 'Database connectivity timeout',
            'status'        => 'open',
            'priority'      => 'urgent',
            'updated_at'    => now(),
        ]);

        $response = $this->actingAs($this->customer)->get(route('customer.dashboard'));

        $response->assertOk();
        $response->assertSee('TCK-RECENT-999');
        $response->assertSee('Database connectivity timeout');
        $response->assertSee(route('customer.tickets.show', $ticket));
    }

    /**
     * Test 3: Dashboard displays recent notifications.
     */
    public function test_customer_dashboard_displays_recent_notifications(): void
    {
        $this->customer->notify(new GeneralNotification(
            'Support Reply Received',
            'Agent updated your ticket',
            'info',
            route('customer.tickets.index')
        ));

        $response = $this->actingAs($this->customer)->get(route('customer.dashboard'));

        $response->assertOk();
        $response->assertSee('Support Reply Received');
        $response->assertSee('Agent updated your ticket');
    }

    /**
     * Test 4: Dashboard displays healthy SLA state when all tickets are on track.
     */
    public function test_customer_dashboard_displays_healthy_sla_state(): void
    {
        Ticket::create([
            'ticket_number'         => 'TCK-HEALTHY-01',
            'customer_id'           => $this->customer->id,
            'subject'               => 'Healthy Ticket',
            'status'                => 'open',
            'priority'              => 'low',
            'first_response_due_at' => now()->addDays(2),
            'resolution_due_at'     => now()->addDays(5),
        ]);

        $response = $this->actingAs($this->customer)->get(route('customer.dashboard'));

        $response->assertOk();
        $response->assertSee('All SLA Guarantees On Track');
    }

    /**
     * Test 5: Dashboard displays SLA warning when a ticket is breached.
     */
    public function test_customer_dashboard_displays_sla_breach_warning(): void
    {
        Ticket::create([
            'ticket_number'         => 'TCK-BREACH-01',
            'customer_id'           => $this->customer->id,
            'subject'               => 'Critical System Down',
            'status'                => 'open',
            'priority'              => 'urgent',
            'first_response_due_at' => now()->subHours(2), // Past deadline -> Breached
        ]);

        $response = $this->actingAs($this->customer)->get(route('customer.dashboard'));

        $response->assertOk();
        $response->assertSee('SLA Milestone Attention Needed');
        $response->assertSee('breached');
    }

    /**
     * Test 6: Dashboard strictly isolates data between customers.
     */
    public function test_customer_dashboard_strictly_isolates_data(): void
    {
        // Other customer's tickets
        Ticket::create([
            'ticket_number' => 'TCK-OTHER-777',
            'customer_id'   => $this->otherCustomer->id,
            'subject'       => 'Confidential Other Customer Issue',
            'status'        => 'open',
            'priority'      => 'high',
        ]);

        // Other customer's notification
        $this->otherCustomer->notify(new GeneralNotification(
            'Secret Other Customer Alert',
            'Private alert message',
            'warning'
        ));

        // Customer's dashboard
        $response = $this->actingAs($this->customer)->get(route('customer.dashboard'));

        $response->assertOk();
        $response->assertDontSee('TCK-OTHER-777');
        $response->assertDontSee('Confidential Other Customer Issue');
        $response->assertDontSee('Secret Other Customer Alert');
        $response->assertViewHas('counts', [
            'total'       => 0,
            'open'        => 0,
            'in_progress' => 0,
            'resolved'    => 0,
            'closed'      => 0,
        ]);
    }

    /**
     * Test 7: Quick action create ticket button is present.
     */
    public function test_quick_action_create_ticket_button_present(): void
    {
        $response = $this->actingAs($this->customer)->get(route('customer.dashboard'));

        $response->assertOk();
        $response->assertSee(route('customer.tickets.create'));
    }

    /**
     * Test 8: Guests cannot access customer dashboard.
     */
    public function test_guest_cannot_access_customer_dashboard(): void
    {
        $response = $this->get(route('customer.dashboard'));
        $response->assertRedirect('/login');
    }
}

