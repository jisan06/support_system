<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class TicketActivityLogCoverageTest extends TestCase
{
    use DatabaseTransactions, CreatesAdminUser;

    protected User $customer;
    protected User $agent;
    protected User $secondAgent;
    protected TicketService $ticketService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ticketService = app(TicketService::class);

        $this->customer = User::create([
            'name'              => 'Coverage Customer',
            'email'             => 'cov_cust_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_CUSTOMER,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);

        $this->agent = $this->createAdminUser(['view_ticket', 'create_ticket', 'edit_ticket']);

        $this->secondAgent = User::create([
            'name'              => 'Second Staff',
            'email'             => 'staff2_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_ADMIN,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Test 1: Ticket creation records department and initial SLA deadlines in activity log.
     */
    public function test_ticket_creation_records_department_and_initial_sla_deadlines(): void
    {
        $this->actingAs($this->agent);

        $ticket = $this->ticketService->createTicket([
            'customer_id' => $this->customer->id,
            'subject'     => 'Activity Coverage Initial Ticket',
            'description' => 'Detailed inquiry text for ticket creation activity verification.',
            'priority'    => 'high',
            'department'  => 'Billing & Payments',
        ], $this->agent);

        $activity = ActivityLog::where('subject_type', Ticket::class)
            ->where('subject_id', $ticket->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($activity, 'Created activity log event for Ticket was not recorded.');
        $this->assertEquals('ticket', $activity->log_name);
        $this->assertEquals($this->agent->id, $activity->causer_id);

        $attributes = $activity->properties['attributes'] ?? [];

        // Assert department and SLA deadlines are recorded
        $this->assertEquals('Billing & Payments', $attributes['department'] ?? null);
        $this->assertEquals('high', $attributes['priority'] ?? null);
        $this->assertNotNull($attributes['first_response_due_at'] ?? null, 'first_response_due_at was missing from creation activity log.');
        $this->assertNotNull($attributes['resolution_due_at'] ?? null, 'resolution_due_at was missing from creation activity log.');
    }

    /**
     * Test 2: Department/category changes are recorded with old and new values.
     */
    public function test_department_changes_are_recorded(): void
    {
        $this->actingAs($this->agent);

        $ticket = Ticket::create([
            'ticket_number' => 'TCK-DEP-' . strtoupper(Str::random(5)),
            'customer_id'   => $this->customer->id,
            'subject'       => 'Department Switch Test',
            'status'        => 'open',
            'priority'      => 'medium',
            'department'    => 'Technical Support',
        ]);

        $ticket->update(['department' => 'Billing & Payments']);

        $activity = ActivityLog::where('subject_type', Ticket::class)
            ->where('subject_id', $ticket->id)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals('Technical Support', $activity->properties['old']['department'] ?? null);
        $this->assertEquals('Billing & Payments', $activity->properties['attributes']['department'] ?? null);
    }

    /**
     * Test 3: Status and priority changes include old and new values.
     */
    public function test_status_and_priority_changes_include_old_and_new_values(): void
    {
        $this->actingAs($this->agent);

        $ticket = Ticket::create([
            'ticket_number' => 'TCK-STAT-' . strtoupper(Str::random(5)),
            'customer_id'   => $this->customer->id,
            'subject'       => 'Status and Priority Test',
            'status'        => 'open',
            'priority'      => 'low',
        ]);

        // Status change
        $ticket->update(['status' => 'in_progress']);

        $statusActivity = ActivityLog::where('subject_type', Ticket::class)
            ->where('subject_id', $ticket->id)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $this->assertEquals('open', $statusActivity->properties['old']['status'] ?? null);
        $this->assertEquals('in_progress', $statusActivity->properties['attributes']['status'] ?? null);

        // Priority change
        $this->ticketService->updatePriority($ticket, 'urgent');

        $priorityActivity = ActivityLog::where('subject_type', Ticket::class)
            ->where('subject_id', $ticket->id)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $this->assertEquals('low', $priorityActivity->properties['old']['priority'] ?? null);
        $this->assertEquals('urgent', $priorityActivity->properties['attributes']['priority'] ?? null);
    }

    /**
     * Test 4: Assignment changes are recorded (manual and reassignments).
     */
    public function test_assignment_changes_are_recorded(): void
    {
        $this->actingAs($this->agent);

        $ticket = Ticket::create([
            'ticket_number' => 'TCK-ASSIGN-' . strtoupper(Str::random(5)),
            'customer_id'   => $this->customer->id,
            'subject'       => 'Assignment Test',
            'status'        => 'open',
            'priority'      => 'medium',
            'assigned_to'   => $this->agent->id,
        ]);

        // Reassign to second agent
        $ticket->update(['assigned_to' => $this->secondAgent->id]);

        $activity = ActivityLog::where('subject_type', Ticket::class)
            ->where('subject_id', $ticket->id)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals($this->agent->id, $activity->properties['old']['assigned_to'] ?? null);
        $this->assertEquals($this->secondAgent->id, $activity->properties['attributes']['assigned_to'] ?? null);
    }

    /**
     * Test 5: SLA deadline changes are recorded in activity log.
     */
    public function test_sla_deadline_changes_are_recorded(): void
    {
        $this->actingAs($this->agent);

        $ticket = Ticket::create([
            'ticket_number'         => 'TCK-SLA-' . strtoupper(Str::random(5)),
            'customer_id'           => $this->customer->id,
            'subject'               => 'SLA Deadline Override Test',
            'status'                => 'open',
            'priority'              => 'medium',
            'first_response_due_at' => now()->addHours(4),
            'resolution_due_at'     => now()->addHours(24),
        ]);

        $newResponseDue = now()->addHours(2);
        $newResolutionDue = now()->addHours(12);

        $ticket->update([
            'first_response_due_at' => $newResponseDue,
            'resolution_due_at'     => $newResolutionDue,
        ]);

        $activity = ActivityLog::where('subject_type', Ticket::class)
            ->where('subject_id', $ticket->id)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertArrayHasKey('first_response_due_at', $activity->properties['old'] ?? []);
        $this->assertArrayHasKey('resolution_due_at', $activity->properties['old'] ?? []);
        $this->assertArrayHasKey('first_response_due_at', $activity->properties['attributes'] ?? []);
        $this->assertArrayHasKey('resolution_due_at', $activity->properties['attributes'] ?? []);
    }

    /**
     * Test 6: Resolution records resolved_at.
     */
    public function test_resolution_records_resolved_at(): void
    {
        $this->actingAs($this->agent);

        $ticket = Ticket::create([
            'ticket_number' => 'TCK-RES-' . strtoupper(Str::random(5)),
            'customer_id'   => $this->customer->id,
            'subject'       => 'Resolution Audit Test',
            'status'        => 'open',
            'priority'      => 'medium',
            'resolved_at'   => null,
        ]);

        $this->ticketService->updateStatus($ticket, 'resolved');

        $activity = ActivityLog::where('subject_type', Ticket::class)
            ->where('subject_id', $ticket->id)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals('open', $activity->properties['old']['status'] ?? null);
        $this->assertEquals('resolved', $activity->properties['attributes']['status'] ?? null);

        // Assert resolved_at transition was recorded
        $this->assertNull($activity->properties['old']['resolved_at'] ?? null);
        $this->assertNotNull($activity->properties['attributes']['resolved_at'] ?? null);
    }
}

