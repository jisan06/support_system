<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketAssignmentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class TicketAssignmentTest extends TestCase
{
    use DatabaseTransactions, CreatesAdminUser;

    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::create([
            'name'              => 'Assignment Customer',
            'email'             => 'cust_assign_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_CUSTOMER,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Helper: Create an internal admin user with specific permissions.
     */
    protected function createAgentWithPermissions(array $permissions, string $status = 'Active'): User
    {
        $role = Role::create([
            'name'   => 'Role ' . Str::random(6),
            'status' => 'Active',
        ]);

        foreach ($permissions as $permission) {
            RolePermission::create([
                'role_id' => $role->id,
                'slug'    => $permission,
            ]);
        }

        $agent = User::create([
            'name'              => 'Agent ' . Str::random(6),
            'email'             => 'agent_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_ADMIN,
            'status'            => $status,
            'email_verified_at' => now(),
        ]);

        $agent->roles()->attach($role->id, ['created_by' => $agent->id]);

        return $agent;
    }

    /**
     * Helper: Create a ticket directly in the database.
     */
    protected function createTicketRecord(?int $assignedTo = null, string $status = 'open'): Ticket
    {
        return Ticket::create([
            'ticket_number' => 'TCK-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
            'customer_id'   => $this->customer->id,
            'assigned_to'   => $assignedTo,
            'subject'       => 'Support Request ' . Str::random(5),
            'status'        => $status,
            'priority'      => 'medium',
            'last_reply_at' => now(),
        ]);
    }

    /**
     * Test 1: Eligible users appear in the manual assignment dropdown on Admin ticket details.
     */
    public function test_eligible_users_appear_in_manual_assignment_dropdown(): void
    {
        $admin = $this->createAdminUser(['view_ticket', 'edit_ticket']);
        $eligibleAgent = $this->createAgentWithPermissions(['receive_ticket_assignments']);
        $ineligibleAgent = $this->createAgentWithPermissions(['view_ticket']); // missing receive_ticket_assignments

        $ticket = $this->createTicketRecord();

        $response = $this->actingAs($admin)->get(route('tickets.show', $ticket->id));

        $response->assertStatus(200);
        $response->assertSee($eligibleAgent->name);
        $response->assertDontSee($ineligibleAgent->name);
        $response->assertSee('Unassigned');
    }

    /**
     * Test 2: Manual assignment works and logs activity.
     */
    public function test_manual_assignment_works_and_logs_activity(): void
    {
        $admin = $this->createAdminUser(['view_ticket', 'edit_ticket']);
        $eligibleAgent = $this->createAgentWithPermissions(['receive_ticket_assignments']);
        $ticket = $this->createTicketRecord();

        $response = $this->actingAs($admin)->patch(route('tickets.assign', $ticket->id), [
            'assigned_to' => $eligibleAgent->id,
        ]);

        $response->assertRedirect(route('tickets.show', $ticket->id));
        $response->assertSessionHas('success');

        $ticket->refresh();
        $this->assertEquals($eligibleAgent->id, $ticket->assigned_to);

        // Verify activity log recorded assignment change
        $activity = Activity::where('subject_type', Ticket::class)
            ->where('subject_id', $ticket->id)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals($eligibleAgent->id, $activity->properties['attributes']['assigned_to'] ?? null);
    }

    /**
     * Test 3: Manual unassignment works.
     */
    public function test_manual_unassignment_works(): void
    {
        $admin = $this->createAdminUser(['view_ticket', 'edit_ticket']);
        $eligibleAgent = $this->createAgentWithPermissions(['receive_ticket_assignments']);
        $ticket = $this->createTicketRecord($eligibleAgent->id);

        $response = $this->actingAs($admin)->patch(route('tickets.assign', $ticket->id), [
            'assigned_to' => '',
        ]);

        $response->assertRedirect(route('tickets.show', $ticket->id));
        $response->assertSessionHas('success');

        $ticket->refresh();
        $this->assertNull($ticket->assigned_to);
    }

    /**
     * Test 4: Manual reassignment to another eligible agent works.
     */
    public function test_manual_reassignment_works(): void
    {
        $admin = $this->createAdminUser(['view_ticket', 'edit_ticket']);
        $agent1 = $this->createAgentWithPermissions(['receive_ticket_assignments']);
        $agent2 = $this->createAgentWithPermissions(['receive_ticket_assignments']);
        $ticket = $this->createTicketRecord($agent1->id);

        $response = $this->actingAs($admin)->patch(route('tickets.assign', $ticket->id), [
            'assigned_to' => $agent2->id,
        ]);

        $response->assertRedirect(route('tickets.show', $ticket->id));
        $response->assertSessionHas('success');

        $ticket->refresh();
        $this->assertEquals($agent2->id, $ticket->assigned_to);
    }

    /**
     * Test 5: Ineligible users cannot be assigned.
     */
    public function test_ineligible_users_cannot_be_assigned(): void
    {
        $admin = $this->createAdminUser(['view_ticket', 'edit_ticket']);
        $ineligibleAgent = $this->createAgentWithPermissions(['view_ticket']); // missing permission
        $ticket = $this->createTicketRecord();

        $response = $this->actingAs($admin)->patch(route('tickets.assign', $ticket->id), [
            'assigned_to' => $ineligibleAgent->id,
        ]);

        $response->assertRedirect(route('tickets.show', $ticket->id));
        $response->assertSessionHasErrors(['assigned_to']);

        $ticket->refresh();
        $this->assertNull($ticket->assigned_to);
    }

    /**
     * Test 6: Inactive admin with permission cannot be assigned.
     */
    public function test_inactive_admin_cannot_be_assigned(): void
    {
        $admin = $this->createAdminUser(['view_ticket', 'edit_ticket']);
        $inactiveAgent = $this->createAgentWithPermissions(['receive_ticket_assignments'], 'Inactive');
        $ticket = $this->createTicketRecord();

        $response = $this->actingAs($admin)->patch(route('tickets.assign', $ticket->id), [
            'assigned_to' => $inactiveAgent->id,
        ]);

        $response->assertRedirect(route('tickets.show', $ticket->id));
        $response->assertSessionHasErrors(['assigned_to']);

        $ticket->refresh();
        $this->assertNull($ticket->assigned_to);
    }

    /**
     * Test 7: Automatic assignment runs on customer ticket creation.
     */
    public function test_automatic_assignment_runs_on_customer_ticket_creation(): void
    {
        $eligibleAgent = $this->createAgentWithPermissions(['receive_ticket_assignments']);

        $response = $this->actingAs($this->customer)->post(route('customer.tickets.store'), [
            'subject'     => 'Customer auto assignment test',
            'department'  => 'Technical Support',
            'priority'    => 'medium',
            'description' => 'Testing customer ticket auto assignment flow.',
        ]);

        $response->assertRedirect(route('customer.dashboard'));

        $ticket = Ticket::where('subject', 'Customer auto assignment test')->first();
        $this->assertNotNull($ticket);
        $this->assertEquals($eligibleAgent->id, $ticket->assigned_to);
    }

    /**
     * Test 8: Automatic assignment runs on admin ticket creation when no agent is selected.
     */
    public function test_automatic_assignment_runs_on_admin_ticket_creation_when_no_agent_selected(): void
    {
        $admin = $this->createAdminUser(['create_ticket', 'view_ticket']);
        $eligibleAgent = $this->createAgentWithPermissions(['receive_ticket_assignments']);

        $response = $this->actingAs($admin)->post(route('tickets.store'), [
            'customer_id' => $this->customer->id,
            'assigned_to' => '',
            'subject'     => 'Admin auto assign without manual agent',
            'priority'    => 'high',
            'description' => 'Admin left agent blank, expecting auto assign.',
        ]);

        $ticket = Ticket::where('subject', 'Admin auto assign without manual agent')->first();
        $this->assertNotNull($ticket);
        $this->assertEquals($eligibleAgent->id, $ticket->assigned_to);
    }

    /**
     * Test 9: Manual assignee is not overwritten when explicitly selected during ticket creation.
     */
    public function test_manual_assignee_is_not_overwritten(): void
    {
        $admin = $this->createAdminUser(['create_ticket', 'view_ticket']);
        $selectedAgent = $this->createAgentWithPermissions(['receive_ticket_assignments']);
        $freeAgent = $this->createAgentWithPermissions(['receive_ticket_assignments']);

        // Give selectedAgent 2 active tickets so freeAgent has lower workload
        $this->createTicketRecord($selectedAgent->id, 'open');
        $this->createTicketRecord($selectedAgent->id, 'open');

        $response = $this->actingAs($admin)->post(route('tickets.store'), [
            'customer_id' => $this->customer->id,
            'assigned_to' => $selectedAgent->id,
            'subject'     => 'Manual selection must be preserved',
            'priority'    => 'low',
            'description' => 'Admin selected specific agent explicitly.',
        ]);

        $ticket = Ticket::where('subject', 'Manual selection must be preserved')->first();
        $this->assertNotNull($ticket);
        // Must stay assigned to selectedAgent, NOT freeAgent
        $this->assertEquals($selectedAgent->id, $ticket->assigned_to);
    }

    /**
     * Test 10: Least active workload strategy selects agent with fewest active tickets.
     */
    public function test_least_workload_selection_works(): void
    {
        $busyAgent = $this->createAgentWithPermissions(['receive_ticket_assignments']);
        $freeAgent = $this->createAgentWithPermissions(['receive_ticket_assignments']);

        // Give busyAgent active tickets in various active statuses (open, in_progress, waiting_for_customer)
        $this->createTicketRecord($busyAgent->id, 'open');
        $this->createTicketRecord($busyAgent->id, 'in_progress');
        $this->createTicketRecord($busyAgent->id, 'waiting_for_customer');

        // Customer creates ticket
        $response = $this->actingAs($this->customer)->post(route('customer.tickets.store'), [
            'subject'     => 'Least workload test ticket',
            'department'  => 'General Inquiry',
            'priority'    => 'medium',
            'description' => 'Should be assigned to the free agent.',
        ]);

        $ticket = Ticket::where('subject', 'Least workload test ticket')->first();
        $this->assertNotNull($ticket);
        $this->assertEquals($freeAgent->id, $ticket->assigned_to);
    }

    /**
     * Test 11: Resolved and closed tickets are excluded from workload counts.
     */
    public function test_resolved_and_closed_tickets_are_excluded_from_workload(): void
    {
        $agentWithResolved = $this->createAgentWithPermissions(['receive_ticket_assignments']);
        $agentWithActive = $this->createAgentWithPermissions(['receive_ticket_assignments']);

        // agentWithResolved has 5 resolved/closed tickets (0 active)
        $this->createTicketRecord($agentWithResolved->id, 'resolved');
        $this->createTicketRecord($agentWithResolved->id, 'resolved');
        $this->createTicketRecord($agentWithResolved->id, 'closed');
        $this->createTicketRecord($agentWithResolved->id, 'closed');
        $this->createTicketRecord($agentWithResolved->id, 'closed');

        // agentWithActive has 1 open ticket
        $this->createTicketRecord($agentWithActive->id, 'open');

        // Customer creates ticket
        $response = $this->actingAs($this->customer)->post(route('customer.tickets.store'), [
            'subject'     => 'Resolved exclusion test ticket',
            'department'  => 'General Inquiry',
            'priority'    => 'medium',
            'description' => 'Should pick agentWithResolved because active count is 0.',
        ]);

        $ticket = Ticket::where('subject', 'Resolved exclusion test ticket')->first();
        $this->assertNotNull($ticket);
        $this->assertEquals($agentWithResolved->id, $ticket->assigned_to);
    }

    /**
     * Test 12: Deterministic tie-breaker selects lowest user ID when workloads are equal.
     */
    public function test_deterministic_tie_breaker_uses_lowest_user_id(): void
    {
        $agentA = $this->createAgentWithPermissions(['receive_ticket_assignments']);
        $agentB = $this->createAgentWithPermissions(['receive_ticket_assignments']);

        $expectedWinner = $agentA->id < $agentB->id ? $agentA : $agentB;

        $response = $this->actingAs($this->customer)->post(route('customer.tickets.store'), [
            'subject'     => 'Tie breaker test ticket',
            'department'  => 'General Inquiry',
            'priority'    => 'medium',
            'description' => 'Both agents have 0 tickets; lowest ID must win.',
        ]);

        $ticket = Ticket::where('subject', 'Tie breaker test ticket')->first();
        $this->assertNotNull($ticket);
        $this->assertEquals($expectedWinner->id, $ticket->assigned_to);
    }

    /**
     * Test 13: When no eligible user exists, ticket is left unassigned.
     */
    public function test_no_eligible_user_results_in_unassigned_ticket(): void
    {
        // No agents have receive_ticket_assignments permission
        $this->createAgentWithPermissions(['view_ticket']);

        $response = $this->actingAs($this->customer)->post(route('customer.tickets.store'), [
            'subject'     => 'No eligible agent test ticket',
            'department'  => 'General Inquiry',
            'priority'    => 'medium',
            'description' => 'Should remain unassigned.',
        ]);

        $ticket = Ticket::where('subject', 'No eligible agent test ticket')->first();
        $this->assertNotNull($ticket);
        $this->assertNull($ticket->assigned_to);
    }

    /**
     * Test 14: Concurrent assignment protection prevents overwriting.
     */
    public function test_concurrent_assignment_protection(): void
    {
        $agent1 = $this->createAgentWithPermissions(['receive_ticket_assignments']);
        $agent2 = $this->createAgentWithPermissions(['receive_ticket_assignments']);

        $ticket = $this->createTicketRecord(null); // unassigned

        $service = app(TicketAssignmentService::class);

        // First agent assigns
        $service->manualAssign($ticket, $agent1->id);
        $this->assertEquals($agent1->id, $ticket->fresh()->assigned_to);

        // Second agent attempts to assign using the old stale ticket reference (which was unassigned)
        $staleTicket = $ticket->replicate();
        $staleTicket->id = $ticket->id;
        $staleTicket->assigned_to = null;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('This ticket has already been assigned to another agent.');

        $service->manualAssign($staleTicket, $agent2->id);
    }
}

