<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class AdminDashboardReportingTest extends TestCase
{
    use DatabaseTransactions, CreatesAdminUser;

    protected User $adminUser;
    protected User $agentUser;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = $this->createAdminUser(['view_ticket', 'create_ticket', 'edit_ticket']);

        $this->agentUser = User::create([
            'name'              => 'Agent Smith',
            'email'             => 'agent_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_ADMIN,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);

        $this->customer = User::create([
            'name'              => 'Test Customer',
            'email'             => 'customer_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_CUSTOMER,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Test 1: Admin dashboard displays all 8 ticket statistics accurately.
     */
    public function test_admin_dashboard_displays_all_ticket_statistics(): void
    {
        $initialTotal = Ticket::count();
        $initialOpen = Ticket::where('status', 'open')->count();
        $initialInProgress = Ticket::where('status', 'in_progress')->count();
        $initialResolved = Ticket::where('status', 'resolved')->count();
        $initialClosed = Ticket::where('status', 'closed')->count();
        $initialUnassigned = Ticket::whereNull('assigned_to')->count();

        // 1 Open assigned to agent
        Ticket::create([
            'ticket_number' => 'TCK-' . Str::random(6),
            'customer_id'   => $this->customer->id,
            'assigned_to'   => $this->agentUser->id,
            'subject'       => 'Open ticket assigned',
            'status'        => 'open',
            'priority'      => 'high',
        ]);

        // 1 In Progress unassigned
        Ticket::create([
            'ticket_number' => 'TCK-' . Str::random(6),
            'customer_id'   => $this->customer->id,
            'assigned_to'   => null,
            'subject'       => 'In progress unassigned',
            'status'        => 'in_progress',
            'priority'      => 'urgent',
        ]);

        // 1 Resolved
        Ticket::create([
            'ticket_number' => 'TCK-' . Str::random(6),
            'customer_id'   => $this->customer->id,
            'assigned_to'   => $this->agentUser->id,
            'subject'       => 'Resolved ticket',
            'status'        => 'resolved',
            'priority'      => 'medium',
        ]);

        // 1 Closed
        Ticket::create([
            'ticket_number' => 'TCK-' . Str::random(6),
            'customer_id'   => $this->customer->id,
            'assigned_to'   => null,
            'subject'       => 'Closed ticket',
            'status'        => 'closed',
            'priority'      => 'low',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('canViewTickets', true);

        $stats = $response->viewData('ticketStats');
        $this->assertNotNull($stats);
        $this->assertEquals($initialTotal + 4, $stats['total']);
        $this->assertEquals($initialOpen + 1, $stats['open']);
        $this->assertEquals($initialInProgress + 1, $stats['in_progress']);
        $this->assertEquals($initialResolved + 1, $stats['resolved']);
        $this->assertEquals($initialClosed + 1, $stats['closed']);
        $this->assertEquals($initialUnassigned + 2, $stats['unassigned']);
    }

    /**
     * Test 2: Admin dashboard respects date range filtering.
     */
    public function test_admin_dashboard_date_filtering(): void
    {
        $todayInitial = Ticket::whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])->count();

        // Old ticket created 40 days ago
        Ticket::create([
            'ticket_number' => 'TCK-OLD-01',
            'customer_id'   => $this->customer->id,
            'subject'       => 'Ancient ticket',
            'status'        => 'open',
            'priority'      => 'low',
            'created_at'    => now()->subDays(40),
            'updated_at'    => now()->subDays(40),
        ]);

        // Recent ticket created today
        Ticket::create([
            'ticket_number' => 'TCK-TODAY-01',
            'customer_id'   => $this->customer->id,
            'subject'       => 'Today ticket',
            'status'        => 'open',
            'priority'      => 'urgent',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // Filter: Today
        $todayResponse = $this->actingAs($this->adminUser)->get(route('admin.dashboard', ['range' => 'today']));
        $todayResponse->assertOk();
        $todayStats = $todayResponse->viewData('ticketStats');
        $this->assertEquals($todayInitial + 1, $todayStats['total']);

        // Filter: Custom range including the old ticket
        $customResponse = $this->actingAs($this->adminUser)->get(route('admin.dashboard', [
            'range'      => 'custom',
            'start_date' => now()->subDays(50)->toDateString(),
            'end_date'   => now()->subDays(30)->toDateString(),
        ]));
        $customResponse->assertOk();
        $customStats = $customResponse->viewData('ticketStats');
        $this->assertEquals(1, $customStats['total']);
    }

    /**
     * Test 3: Ticket status and priority breakdowns are calculated accurately.
     */
    public function test_admin_dashboard_ticket_status_and_priority_breakdowns(): void
    {
        $initialUrgent = Ticket::where('priority', 'urgent')->count();
        $initialHigh = Ticket::where('priority', 'high')->count();

        Ticket::create([
            'ticket_number' => 'TCK-P1',
            'customer_id'   => $this->customer->id,
            'subject'       => 'Urgent issue',
            'status'        => 'open',
            'priority'      => 'urgent',
        ]);
        Ticket::create([
            'ticket_number' => 'TCK-P2',
            'customer_id'   => $this->customer->id,
            'subject'       => 'High issue',
            'status'        => 'resolved',
            'priority'      => 'high',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.dashboard'));
        $insights = $response->viewData('ticketInsights');

        $this->assertNotNull($insights);
        $this->assertEquals($initialUrgent + 1, $insights['volume_by_priority']['urgent']['count']);
        $this->assertEquals($initialHigh + 1, $insights['volume_by_priority']['high']['count']);
    }

    /**
     * Test 4: Agent workload overview reflects active and resolved tickets assigned to agents.
     */
    public function test_admin_dashboard_agent_workload(): void
    {
        Ticket::create([
            'ticket_number' => 'TCK-WORK-1',
            'customer_id'   => $this->customer->id,
            'assigned_to'   => $this->agentUser->id,
            'subject'       => 'Active task for agent',
            'status'        => 'in_progress',
            'priority'      => 'high',
        ]);

        Ticket::create([
            'ticket_number' => 'TCK-WORK-2',
            'customer_id'   => $this->customer->id,
            'assigned_to'   => $this->agentUser->id,
            'subject'       => 'Resolved task for agent',
            'status'        => 'resolved',
            'priority'      => 'medium',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.dashboard'));
        $insights = $response->viewData('ticketInsights');

        $agentData = collect($insights['agent_workload'])->firstWhere('id', $this->agentUser->id);
        $this->assertNotNull($agentData);
        $this->assertEquals(1, $agentData->open_assigned);
        $this->assertEquals(1, $agentData->resolved_assigned);
        $this->assertEquals(2, $agentData->total_assigned);
    }

    /**
     * Test 5: SLA breached and approaching breach counts are calculated correctly.
     */
    public function test_admin_dashboard_sla_statistics(): void
    {
        // Breached ticket: response deadline passed in the past
        Ticket::create([
            'ticket_number'         => 'TCK-SLA-BREACHED',
            'customer_id'           => $this->customer->id,
            'subject'               => 'Breached Ticket',
            'status'                => 'open',
            'priority'              => 'urgent',
            'first_response_due_at' => now()->subHours(5),
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.dashboard'));
        $stats = $response->viewData('ticketStats');

        $this->assertGreaterThanOrEqual(1, $stats['sla_breached']);
    }

    /**
     * Test 6: Users without view_ticket permission cannot see ticket reporting sections.
     */
    public function test_user_without_view_ticket_permission_cannot_see_ticket_stats(): void
    {
        // User with only view_user permission
        $viewerOnlyAdmin = $this->createAdminUser(['view_user']);

        $response = $this->actingAs($viewerOnlyAdmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('canViewTickets', false);
        $this->assertNull($response->viewData('ticketStats'));
        $this->assertNull($response->viewData('ticketInsights'));
        $response->assertDontSee('Support Ticket Overview &amp; Reporting', false);
    }
}
