<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Ticket;
use App\Models\TicketEscalation;
use App\Models\User;
use App\Services\SlaEscalationService;
use App\Services\TicketService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class TicketSlaEscalationTest extends TestCase
{
    use DatabaseTransactions, CreatesAdminUser;

    protected User $customer;
    protected User $agent;
    protected User $supervisor;
    protected TicketService $ticketService;
    protected SlaEscalationService $escalationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ticketService = app(TicketService::class);
        $this->escalationService = app(SlaEscalationService::class);

        $this->customer = User::create([
            'name'              => 'Escalation Customer',
            'email'             => 'esc_customer_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_CUSTOMER,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);

        // Standard support staff role with level 10
        $agentRole = Role::create([
            'name'   => ['en' => 'Support Agent'],
            'level'  => 10,
            'status' => 'Active',
        ]);
        RolePermission::create(['role_id' => $agentRole->id, 'slug' => 'view_ticket']);
        RolePermission::create(['role_id' => $agentRole->id, 'slug' => 'reply_ticket']);

        $this->agent = User::create([
            'name'              => 'Frontline Agent',
            'email'             => 'agent_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_ADMIN,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);
        $this->agent->roles()->attach($agentRole->id, ['created_by' => $this->agent->id]);

        // Supervisor role with level 5 (higher authority than agent)
        $supervisorRole = Role::create([
            'name'   => ['en' => 'Support Supervisor'],
            'level'  => 5,
            'status' => 'Active',
        ]);
        RolePermission::create(['role_id' => $supervisorRole->id, 'slug' => 'view_ticket']);
        RolePermission::create(['role_id' => $supervisorRole->id, 'slug' => 'reply_ticket']);

        $this->supervisor = User::create([
            'name'              => 'Senior Supervisor',
            'email'             => 'sup_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_ADMIN,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);
        $this->supervisor->roles()->attach($supervisorRole->id, ['created_by' => $this->supervisor->id]);
    }

    /**
     * Test 1: Escalation rule matching for priorities and breach conditions.
     */
    public function test_escalation_rule_matching(): void
    {
        $ticket = $this->ticketService->createTicket([
            'customer_id' => $this->customer->id,
            'assigned_to' => $this->agent->id,
            'subject'     => 'Rule Matching Test',
            'priority'    => 'urgent',
            'description' => 'Testing escalation rules generation.',
        ], $this->agent);

        $rules = $this->escalationService->getRulesForTicket($ticket);

        $this->assertNotEmpty($rules);
        $types = array_column($rules, 'type');
        $this->assertContains('first_response', $types);
        $this->assertContains('resolution', $types);

        // Ticket has just been created, so deadline is in the future
        $level1Rule = $rules[0];
        $this->assertFalse(
            $this->escalationService->shouldEscalate($ticket, $level1Rule, now()),
            'Newly created ticket must not match escalation rule before deadline.'
        );
    }

    /**
     * Test 2: First-response SLA escalation when first public reply is overdue.
     */
    public function test_first_response_sla_escalation(): void
    {
        // Monday 09:00 AM
        $startTime = Carbon::parse('2026-06-01 09:00:00', 'UTC');
        Carbon::setTestNow($startTime);

        $ticket = $this->ticketService->createTicket([
            'customer_id' => $this->customer->id,
            'assigned_to' => $this->agent->id,
            'subject'     => 'First Response Overdue Test',
            'priority'    => 'urgent',
            'description' => 'Needs fast response.',
        ], $this->agent);

        $dueAt = $ticket->first_response_due_at;
        $this->assertNotNull($dueAt);

        // Advance time 5 minutes past first response deadline without response
        $overdueTime = $dueAt->copy()->addMinutes(5);
        Carbon::setTestNow($overdueTime);

        // Process escalations
        $stats = $this->escalationService->processEscalations(50, $overdueTime);

        $this->assertGreaterThan(0, $stats['escalated']);
        $ticket->refresh();

        $this->assertEquals(1, $ticket->escalation_level);
        $this->assertNotNull($ticket->last_escalated_at);

        // Verify history record created
        $history = TicketEscalation::where('ticket_id', $ticket->id)
            ->where('level', 1)
            ->where('trigger_reason', 'first_response_breached')
            ->first();

        $this->assertNotNull($history);
        $this->assertEquals($this->agent->id, $history->previous_assigned_to);

        Carbon::setTestNow(null);
    }

    /**
     * Test 3: Resolution SLA escalation when ticket remains unresolved.
     */
    public function test_resolution_sla_escalation(): void
    {
        $startTime = Carbon::parse('2026-06-01 09:00:00', 'UTC');
        Carbon::setTestNow($startTime);

        $ticket = $this->ticketService->createTicket([
            'customer_id' => $this->customer->id,
            'assigned_to' => $this->agent->id,
            'subject'     => 'Resolution Overdue Test',
            'priority'    => 'high',
            'description' => 'Fixing production issue.',
        ], $this->agent);

        // Satisfy first response milestone
        $ticket->first_responded_at = $startTime->copy()->addMinutes(15);
        $ticket->save();

        // Advance time past resolution deadline
        $resolutionDue = $ticket->resolution_due_at;
        $pastResolution = $resolutionDue->copy()->addMinutes(10);
        Carbon::setTestNow($pastResolution);

        $stats = $this->escalationService->processEscalations(50, $pastResolution);

        $this->assertGreaterThan(0, $stats['escalated']);

        $history = TicketEscalation::where('ticket_id', $ticket->id)
            ->where('trigger_reason', 'resolution_breached')
            ->first();

        $this->assertNotNull($history);
        $this->assertEquals(1, $history->level);

        Carbon::setTestNow(null);
    }

    /**
     * Test 4: Multiple escalation levels and higher-level staff reassignment.
     */
    public function test_multiple_escalation_levels_and_reassignment(): void
    {
        $startTime = Carbon::parse('2026-06-01 09:00:00', 'UTC');
        Carbon::setTestNow($startTime);

        $ticket = $this->ticketService->createTicket([
            'customer_id' => $this->customer->id,
            'assigned_to' => $this->agent->id,
            'subject'     => 'Multi Level Escalation',
            'priority'    => 'urgent',
            'description' => 'Critical ticket testing multiple levels.',
        ], $this->agent);

        $dueAt = $ticket->first_response_due_at;

        // Level 1 immediately past breach
        $level1Time = $dueAt->copy()->addMinutes(1);
        Carbon::setTestNow($level1Time);
        $this->escalationService->processEscalations(50, $level1Time);

        $ticket->refresh();
        $this->assertEquals(1, $ticket->escalation_level);
        $this->assertEquals($this->agent->id, $ticket->assigned_to);

        // Level 2 threshold (60+ minutes past breach)
        $level2Time = $dueAt->copy()->addMinutes(65);
        Carbon::setTestNow($level2Time);
        $this->escalationService->processEscalations(50, $level2Time);

        $ticket->refresh();
        $this->assertEquals(2, $ticket->escalation_level);

        // Level 2 rule has action 'notify_and_reassign', so ticket should be reassigned to eligible higher-level staff (roleLevel < 10)
        $this->assertNotEquals($this->agent->id, $ticket->assigned_to, 'Ticket should be reassigned away from agent.');
        $newAssignee = User::find($ticket->assigned_to);
        $this->assertNotNull($newAssignee);
        $this->assertLessThan($this->agent->roleLevel(), $newAssignee->roleLevel(), 'New assignee must have higher authority level.');
        $this->assertTrue($newAssignee->hasPermissionTo('reply_ticket') || $newAssignee->hasPermissionTo('view_ticket'));

        $level2Record = TicketEscalation::where('ticket_id', $ticket->id)
            ->where('level', 2)
            ->first();

        $this->assertNotNull($level2Record);
        $this->assertEquals($this->agent->id, $level2Record->previous_assigned_to);
        $this->assertEquals($newAssignee->id, $level2Record->new_assigned_to);

        Carbon::setTestNow(null);
    }

    /**
     * Test 5: Duplicate prevention prevents executing the same level multiple times.
     */
    public function test_duplicate_prevention(): void
    {
        $startTime = Carbon::parse('2026-06-01 09:00:00', 'UTC');
        Carbon::setTestNow($startTime);

        $ticket = $this->ticketService->createTicket([
            'customer_id' => $this->customer->id,
            'assigned_to' => $this->agent->id,
            'subject'     => 'Duplicate Prevention Test',
            'priority'    => 'urgent',
            'description' => 'Test duplicate handling.',
        ], $this->agent);

        $overdue = $ticket->first_response_due_at->copy()->addMinutes(5);
        Carbon::setTestNow($overdue);

        // Run process twice consecutively
        $stats1 = $this->escalationService->processEscalations(50, $overdue);
        $stats2 = $this->escalationService->processEscalations(50, $overdue);

        $this->assertGreaterThan(0, $stats1['escalated']);
        $this->assertEquals(0, $stats2['escalated'], 'Second execution must not duplicate already executed escalations.');

        $count = TicketEscalation::where('ticket_id', $ticket->id)
            ->where('level', 1)
            ->where('trigger_reason', 'first_response_breached')
            ->count();

        $this->assertEquals(1, $count);

        Carbon::setTestNow(null);
    }

    /**
     * Test 6: Closed and resolved tickets are excluded from escalation.
     */
    public function test_closed_and_resolved_ticket_exclusion(): void
    {
        $startTime = Carbon::parse('2026-06-01 09:00:00', 'UTC');
        Carbon::setTestNow($startTime);

        $resolvedTicket = $this->ticketService->createTicket([
            'customer_id' => $this->customer->id,
            'assigned_to' => $this->agent->id,
            'subject'     => 'Resolved Ticket Escalation Exclusion',
            'priority'    => 'urgent',
            'description' => 'Was resolved before breach.',
        ], $this->agent);

        $this->ticketService->updateStatus($resolvedTicket, 'resolved');

        $closedTicket = $this->ticketService->createTicket([
            'customer_id' => $this->customer->id,
            'assigned_to' => $this->agent->id,
            'subject'     => 'Closed Ticket Escalation Exclusion',
            'priority'    => 'urgent',
            'description' => 'Was closed directly.',
        ], $this->agent);

        $this->ticketService->updateStatus($closedTicket, 'closed');

        // Move time past all deadlines
        $pastAll = $startTime->copy()->addDays(5);
        Carbon::setTestNow($pastAll);

        $stats = $this->escalationService->processEscalations(50, $pastAll);

        $this->assertFalse(TicketEscalation::where('ticket_id', $resolvedTicket->id)->exists());
        $this->assertFalse(TicketEscalation::where('ticket_id', $closedTicket->id)->exists());

        Carbon::setTestNow(null);
    }

    /**
     * Test 7: Failed job recovery ensures a failure on one ticket does not halt processing of other tickets.
     */
    public function test_failed_job_recovery(): void
    {
        $startTime = Carbon::parse('2026-06-01 09:00:00', 'UTC');
        Carbon::setTestNow($startTime);

        $ticketA = $this->ticketService->createTicket([
            'customer_id' => $this->customer->id,
            'assigned_to' => $this->agent->id,
            'subject'     => 'Failing Ticket',
            'priority'    => 'urgent',
            'description' => 'Will simulate error on escalation.',
        ], $this->agent);

        $ticketB = $this->ticketService->createTicket([
            'customer_id' => $this->customer->id,
            'assigned_to' => $this->agent->id,
            'subject'     => 'Healthy Ticket',
            'priority'    => 'urgent',
            'description' => 'Should still be escalated despite previous ticket failure.',
        ], $this->agent);

        $overdue = $startTime->copy()->addHours(2);
        Carbon::setTestNow($overdue);

        // Subclass service to simulate an error on ticketA only
        $resilientService = new class(app(\App\Services\SlaService::class), app(\App\Services\BusinessHoursService::class), $ticketA->id) extends SlaEscalationService {
            protected int $failingId;

            public function __construct($sla, $bh, int $failingId)
            {
                parent::__construct($sla, $bh);
                $this->failingId = $failingId;
            }

            public function executeEscalation(Ticket $ticket, array $rule, ?Carbon $now = null): ?TicketEscalation
            {
                if ($ticket->id === $this->failingId) {
                    throw new \RuntimeException("Simulated network or lock error on ticket #{$this->failingId}");
                }
                return parent::executeEscalation($ticket, $rule, $now);
            }
        };

        $stats = $resilientService->processEscalations(50, $overdue);

        // Ticket A failed with error recorded, but Ticket B was successfully escalated!
        $this->assertGreaterThanOrEqual(1, $stats['errors'], 'Failed ticket must increment error count.');
        $this->assertGreaterThanOrEqual(1, $stats['escalated'], 'Healthy ticket must be escalated.');
        $this->assertTrue(TicketEscalation::where('ticket_id', $ticketB->id)->exists(), 'Ticket B must be escalated.');

        Carbon::setTestNow(null);
    }

    /**
     * Test 8: Artisan command ticket:escalate-sla executes successfully.
     */
    public function test_artisan_command_execution(): void
    {
        $this->artisan('ticket:escalate-sla', ['--chunk' => 10])
            ->assertExitCode(0)
            ->expectsOutputToContain('SLA escalation processing complete.');
    }
}
