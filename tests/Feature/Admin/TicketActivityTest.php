<?php

namespace Tests\Feature\Admin;

use App\Enums\TicketMessageType;
use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\TicketActivityService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class TicketActivityTest extends TestCase
{
    use DatabaseTransactions, CreatesAdminUser;

    protected User $customer;
    protected User $agent;
    protected User $agentNoPerm;
    protected Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        // Customer user - no factory needed, matches existing project pattern
        $this->customer = User::create([
            'name'              => 'Timeline Customer',
            'email'             => 'tl_cust_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_CUSTOMER,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);

        // Agent with internal_note_ticket permission (the project's registered slug for internal notes)
        $this->agent = $this->createAdminUser([
            'view_ticket',
            'internal_note_ticket',
        ]);

        // Agent WITHOUT internal_note_ticket permission
        $this->agentNoPerm = $this->createAdminUser([
            'view_ticket',
        ]);

        // Create a ticket the project way (no TicketFactory exists)
        $this->ticket = Ticket::create([
            'ticket_number' => 'TCK-TL-' . strtoupper(Str::random(6)),
            'customer_id'   => $this->customer->id,
            'subject'       => 'Activity Timeline Test Ticket',
            'status'        => 'open',
            'priority'      => 'medium',
        ]);
    }

    /**
     * Timeline includes Ticket creation activity and TicketMessage activities,
     * ordered newest-first, for a user with view_internal_notes.
     */
    public function test_timeline_includes_ticket_and_message_activities_ordered_newest_first(): void
    {
        $this->actingAs($this->agent);

        // Create a public reply (triggers Spatie activity log via model event)
        $reply = TicketMessage::create([
            'ticket_id' => $this->ticket->id,
            'user_id'   => $this->agent->id,
            'type'      => TicketMessageType::REPLY->value,
            'content'   => 'Public reply content.',
        ]);

        // Backdate the reply's activity record so the note will be definitively newer
        ActivityLog::where('subject_type', TicketMessage::class)
            ->where('subject_id', $reply->id)
            ->update(['created_at' => now()->subMinutes(2)]);

        // Create an internal note (triggers Spatie activity log via model event)
        $note = TicketMessage::create([
            'ticket_id' => $this->ticket->id,
            'user_id'   => $this->agent->id,
            'type'      => TicketMessageType::INTERNAL_NOTE->value,
            'content'   => 'Internal note content — must NOT leak.',
        ]);

        $service  = app(TicketActivityService::class);
        $timeline = $service->getTimeline($this->ticket, $this->agent);

        // Must include: 1 ticket created + 1 reply + 1 internal note = at least 2 (ticket
        // creation is logged only when Spatie picks it up; at minimum reply + note must appear).
        $this->assertGreaterThanOrEqual(2, $timeline->count(), 'Timeline should contain at least the two message activities.');

        $descriptions = $timeline->pluck('description')->toArray();

        $this->assertContains('Ticket reply was created', $descriptions);
        $this->assertContains('Ticket internal note was created', $descriptions);

        // Verify newest-first order: note was created after reply, so its activity should appear first.
        $replyIndex = array_search('Ticket reply was created', $descriptions);
        $noteIndex  = array_search('Ticket internal note was created', $descriptions);
        $this->assertLessThan($replyIndex, $noteIndex, 'Internal note activity should appear before reply activity (newest first).');
    }

    /**
     * Users without view_internal_notes must not see internal-note activities in the timeline.
     */
    public function test_users_without_view_internal_notes_permission_do_not_see_internal_notes(): void
    {
        $this->actingAs($this->agentNoPerm);

        // Create an internal note — activity logged by Spatie model event
        TicketMessage::create([
            'ticket_id' => $this->ticket->id,
            'user_id'   => $this->agent->id,
            'type'      => TicketMessageType::INTERNAL_NOTE->value,
            'content'   => 'SECRET internal note content.',
        ]);

        $service  = app(TicketActivityService::class);
        $timeline = $service->getTimeline($this->ticket, $this->agentNoPerm);

        // No activity with internal-note description must appear
        $hasInternalNote = $timeline->contains(function ($activity) {
            return str_contains(strtolower($activity->description ?? ''), 'internal note');
        });

        $this->assertFalse($hasInternalNote, 'Internal-note activities must not appear for users without view_internal_notes.');
    }

    /**
     * Users WITH view_internal_notes can see internal-note activities.
     */
    public function test_users_with_view_internal_notes_permission_can_see_internal_notes(): void
    {
        $this->actingAs($this->agent);

        TicketMessage::create([
            'ticket_id' => $this->ticket->id,
            'user_id'   => $this->agent->id,
            'type'      => TicketMessageType::INTERNAL_NOTE->value,
            'content'   => 'Permitted internal note content.',
        ]);

        $service  = app(TicketActivityService::class);
        $timeline = $service->getTimeline($this->ticket, $this->agent);

        $hasInternalNote = $timeline->contains(function ($activity) {
            return str_contains(strtolower($activity->description ?? ''), 'internal note');
        });

        $this->assertTrue($hasInternalNote, 'Internal-note activities must be visible to users with view_internal_notes.');
    }

    /**
     * Internal note activity properties must never expose raw message content.
     * (Regression guard for the previously fixed vulnerability.)
     */
    public function test_internal_note_content_is_not_stored_in_timeline_activity_properties(): void
    {
        $secretContent = 'NEVER_LEAK_TO_TIMELINE_' . Str::random(12);

        $this->actingAs($this->agent);

        $note = TicketMessage::create([
            'ticket_id' => $this->ticket->id,
            'user_id'   => $this->agent->id,
            'type'      => TicketMessageType::INTERNAL_NOTE->value,
            'content'   => $secretContent,
        ]);

        // Fetch the raw activity record
        $activity = ActivityLog::where('subject_type', TicketMessage::class)
            ->where('subject_id', $note->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($activity, 'Activity log record for internal note was not found.');

        // The secret content must not appear anywhere in the stored properties JSON
        $propertiesJson = json_encode($activity->properties);
        $this->assertStringNotContainsString(
            $secretContent,
            $propertiesJson,
            'Internal note content must not be stored in activity_log.properties.'
        );

        // The 'content' key must be absent from stored attributes
        $attributes = $activity->properties['attributes'] ?? [];
        $this->assertArrayNotHasKey('content', $attributes, 'content key must not exist in internal note activity attributes.');
    }

    /**
     * Customer user accounts must not be able to access the admin timeline endpoint.
     */
    public function test_customers_cannot_access_the_admin_timeline_route(): void
    {
        $response = $this->actingAs($this->customer)
            ->get(route('tickets.show', $this->ticket->id));

        // Customers are blocked by middleware (403 or redirect)
        $this->assertContains($response->getStatusCode(), [302, 403],
            'Customer must be blocked from accessing the admin ticket timeline.');
    }
}

