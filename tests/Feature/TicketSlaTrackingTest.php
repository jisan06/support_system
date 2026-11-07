<?php

namespace Tests\Feature;

use App\Enums\SlaStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Services\BusinessHoursService;
use App\Services\SlaCalculationService;
use App\Services\SlaService;
use App\Services\TicketService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class TicketSlaTrackingTest extends TestCase
{
    use DatabaseTransactions, CreatesAdminUser;

    protected User $customer;
    protected User $agent;
    protected TicketService $ticketService;
    protected SlaService $slaService;
    protected SlaCalculationService $calculationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ticketService = app(TicketService::class);
        $this->slaService = app(SlaService::class);
        $this->calculationService = app(SlaCalculationService::class);

        $this->customer = User::create([
            'name' => 'SLA Customer',
            'email' => 'sla_customer_' . Str::random(8) . '@example.com',
            'password' => Hash::make('password123'),
            'account_type' => User::TYPE_CUSTOMER,
            'status' => 'Active',
            'email_verified_at' => now(),
        ]);

        $this->agent = $this->createAdminUser(['view_ticket', 'create_ticket', 'reply_ticket', 'internal_note_ticket']);
    }

    /**
     * Test 1: SLA assignment during ticket creation persists deadlines and leaves first_responded_at null.
     */
    public function test_sla_assignment_during_ticket_creation(): void
    {
        $ticket = $this->ticketService->createTicket([
            'customer_id' => $this->customer->id,
            'subject'     => 'Server Outage Test',
            'priority'    => 'urgent',
            'description' => 'The production server is currently down.',
        ], $this->agent);

        $this->assertNotNull($ticket->first_response_due_at);
        $this->assertNotNull($ticket->resolution_due_at);
        $this->assertNull($ticket->first_responded_at, 'Initial creation message must not count as first response.');
        $this->assertNull($ticket->resolved_at);

        $this->assertTrue($ticket->first_response_due_at->lte($ticket->resolution_due_at));
        $this->assertSame('urgent', $ticket->priority);
    }

    /**
     * Test 2: First public reply by an authorized staff member records first_responded_at, and subsequent replies do not overwrite it.
     */
    public function test_first_public_reply_tracking(): void
    {
        $ticket = $this->ticketService->createTicket([
            'customer_id' => $this->customer->id,
            'subject'     => 'Payment Issue',
            'priority'    => 'high',
            'description' => 'Cannot checkout with credit card.',
        ], $this->agent);

        $this->assertNull($ticket->fresh()->first_responded_at);

        // First public reply via HTTP
        $replyTime = now()->subMinutes(10);
        Carbon::setTestNow($replyTime);

        $response = $this->actingAs($this->agent)->post(route('tickets.reply', $ticket->id), [
            'content' => 'We are investigating your issue right now.',
        ]);

        $response->assertRedirect(route('tickets.show', $ticket->id));
        $ticket->refresh();

        $this->assertNotNull($ticket->first_responded_at);
        $recordedTime = $ticket->first_responded_at->timestamp;
        $this->assertEquals($replyTime->timestamp, $recordedTime);

        // Subsequent public reply 30 minutes later
        Carbon::setTestNow($replyTime->copy()->addMinutes(30));

        $this->actingAs($this->agent)->post(route('tickets.reply', $ticket->id), [
            'content' => 'Another update: the payment gateway has replied.',
        ]);

        $ticket->refresh();
        $this->assertEquals($recordedTime, $ticket->first_responded_at->timestamp, 'Subsequent replies must not overwrite first_responded_at.');

        Carbon::setTestNow(null);
    }

    /**
     * Test 3: Internal notes must never count as a first response.
     */
    public function test_internal_notes_not_counting_as_first_response(): void
    {
        $ticket = $this->ticketService->createTicket([
            'customer_id' => $this->customer->id,
            'subject'     => 'Security Inquiry',
            'priority'    => 'medium',
            'description' => 'Inquiring about data retention policy.',
        ], $this->agent);

        $this->assertNull($ticket->fresh()->first_responded_at);

        // Agent adds an internal note
        $response = $this->actingAs($this->agent)->post(route('tickets.internal_note', $ticket->id), [
            'content' => 'Internal check: Verify data retention documentation before answering.',
        ]);

        $response->assertRedirect(route('tickets.show', $ticket->id));
        $this->assertNull($ticket->fresh()->first_responded_at, 'Internal note must never count as first response.');
    }

    /**
     * Test 4: Resolution timestamp is recorded when status is resolved, not set when closed directly, and reset if reopened.
     */
    public function test_resolution_timestamp_tracking(): void
    {
        $ticket = $this->ticketService->createTicket([
            'customer_id' => $this->customer->id,
            'subject'     => 'Bug Report',
            'priority'    => 'low',
            'description' => 'Typo on the about page.',
        ], $this->agent);

        $this->assertNull($ticket->resolved_at);

        // Closed directly without being resolved does NOT set resolved_at
        $this->ticketService->updateStatus($ticket, 'closed');
        $this->assertNull($ticket->fresh()->resolved_at, 'Closed status must not be treated interchangeably with resolved.');

        // Mark as resolved
        $resolveTime = now();
        Carbon::setTestNow($resolveTime);

        $this->ticketService->updateStatus($ticket, 'resolved');
        $ticket->refresh();
        $this->assertNotNull($ticket->resolved_at);
        $this->assertEquals($resolveTime->timestamp, $ticket->resolved_at->timestamp);

        // Reopening ticket to in_progress clears resolved_at
        $this->ticketService->updateStatus($ticket, 'in_progress');
        $ticket->refresh();
        $this->assertNull($ticket->resolved_at, 'Reopening ticket must clear resolved_at.');

        Carbon::setTestNow(null);
    }

    /**
     * Test 5: SLA Status calculation accurately reflects On Track, Approaching Breach, Breached, and Completed.
     */
    public function test_sla_status_calculation(): void
    {
        // Simulate a Monday 09:00 AM ticket
        $monday9am = Carbon::parse('2026-06-01 09:00:00', 'UTC');
        Carbon::setTestNow($monday9am);

        $ticket = $this->ticketService->createTicket([
            'customer_id' => $this->customer->id,
            'subject'     => 'SLA Status Test',
            'priority'    => 'urgent', // 60 min response, 240 min resolution
            'description' => 'Test SLA status stages.',
        ], $this->agent);

        // At 09:00 AM (just created), response is due at 10:00 AM -> ON_TRACK
        $this->assertEquals(SlaStatus::ON_TRACK, $this->slaService->getFirstResponseStatus($ticket, $monday9am));
        $this->assertEquals(SlaStatus::ON_TRACK, $this->slaService->getOverallStatus($ticket, $monday9am));

        // At 09:50 AM (50 min elapsed, 10 min left <= 20% remaining) -> APPROACHING_BREACH
        $approaching = Carbon::parse('2026-06-01 09:50:00', 'UTC');
        $this->assertEquals(SlaStatus::APPROACHING_BREACH, $this->slaService->getFirstResponseStatus($ticket, $approaching));
        $this->assertEquals(SlaStatus::APPROACHING_BREACH, $this->slaService->getOverallStatus($ticket, $approaching));

        // At 10:05 AM (passed 10:00 AM without response) -> BREACHED
        $breached = Carbon::parse('2026-06-01 10:05:00', 'UTC');
        $this->assertEquals(SlaStatus::BREACHED, $this->slaService->getFirstResponseStatus($ticket, $breached));
        $this->assertEquals(SlaStatus::BREACHED, $this->slaService->getOverallStatus($ticket, $breached));

        // Now test on-time completion
        $ticket->first_responded_at = Carbon::parse('2026-06-01 09:30:00', 'UTC');
        $ticket->save();

        $this->assertEquals(SlaStatus::COMPLETED, $this->slaService->getFirstResponseStatus($ticket));

        // Test resolution completed within deadline
        $ticket->resolved_at = Carbon::parse('2026-06-01 11:00:00', 'UTC'); // Due is 13:00
        $ticket->status = 'resolved';
        $ticket->save();

        $this->assertEquals(SlaStatus::COMPLETED, $this->slaService->getResolutionStatus($ticket));
        $this->assertEquals(SlaStatus::COMPLETED, $this->slaService->getOverallStatus($ticket));

        Carbon::setTestNow(null);
    }

    /**
     * Test 6: Priority changes recalculate pending SLA deadlines from creation time and preserve historical timestamps.
     */
    public function test_priority_change_behavior(): void
    {
        $start = Carbon::parse('2026-06-01 09:00:00', 'UTC');
        Carbon::setTestNow($start);

        // Create low priority ticket (first response: 480 mins, resolution: 2880 mins)
        $ticket = $this->ticketService->createTicket([
            'customer_id' => $this->customer->id,
            'subject'     => 'Escalation Test',
            'priority'    => 'low',
            'description' => 'Started as low priority.',
        ], $this->agent);

        $originalFirstResponseDue = $ticket->first_response_due_at->copy();
        $originalResolutionDue = $ticket->resolution_due_at->copy();

        // 1 hour later, record first response
        Carbon::setTestNow($start->copy()->addHour());
        $this->slaService->recordFirstResponse($ticket, $this->agent);
        $ticket->refresh();
        $firstResponseTimestamp = $ticket->first_responded_at->copy();

        // Escalate priority from low to urgent
        $this->ticketService->updatePriority($ticket, 'urgent');
        $ticket->refresh();

        $this->assertSame('urgent', $ticket->priority);
        // Historical first_responded_at MUST be preserved
        $this->assertEquals($firstResponseTimestamp->timestamp, $ticket->first_responded_at->timestamp);
        // Since first response already occurred, its due date is preserved as historical record
        $this->assertEquals($originalFirstResponseDue->timestamp, $ticket->first_response_due_at->timestamp);
        // Pending resolution deadline is recalculated to urgent target (240 min = 4h from start -> 13:00)
        $this->assertNotEquals($originalResolutionDue->timestamp, $ticket->resolution_due_at->timestamp);
        $this->assertTrue($ticket->resolution_due_at->lt($originalResolutionDue));

        Carbon::setTestNow(null);
    }
}
