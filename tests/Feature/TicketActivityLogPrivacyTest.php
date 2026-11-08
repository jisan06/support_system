<?php

namespace Tests\Feature;

use App\Enums\TicketMessageType;
use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\TicketMessageService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class TicketActivityLogPrivacyTest extends TestCase
{
    use DatabaseTransactions, CreatesAdminUser;

    protected User $customer;
    protected User $agent;
    protected Ticket $ticket;
    protected TicketMessageService $messageService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->messageService = app(TicketMessageService::class);

        $this->customer = User::create([
            'name'              => 'Jane Customer',
            'email'             => 'cust_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_CUSTOMER,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);

        $this->agent = $this->createAdminUser(['view_ticket', 'reply_ticket', 'internal_note_ticket', 'view_activity_log']);

        $this->ticket = Ticket::create([
            'ticket_number' => 'TCK-PRIVACY-' . strtoupper(Str::random(5)),
            'customer_id'   => $this->customer->id,
            'subject'       => 'Privacy Audit Verification Ticket',
            'status'        => 'open',
            'priority'      => 'high',
        ]);
    }

    /**
     * Requirement: Internal note content must NOT be stored in activity log properties,
     * but internal note activity metadata (ticket ID, user ID, message type, causer, subject)
     * must still be recorded.
     */
    public function test_internal_note_content_is_not_stored_in_activity_log_properties(): void
    {
        $secretContent = 'CONFIDENTIAL_NOTE_NEVER_LEAK_TO_ACTIVITY_LOG_' . Str::random(12);

        $this->actingAs($this->agent);

        $internalNote = $this->messageService->addInternalNote(
            $this->ticket,
            $this->agent,
            $secretContent
        );

        $this->assertInstanceOf(TicketMessage::class, $internalNote);
        $this->assertTrue($internalNote->isInternalNote());

        // Find the Spatie activity log record generated for this message
        $activity = ActivityLog::where('subject_type', TicketMessage::class)
            ->where('subject_id', $internalNote->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($activity, 'Activity log record for TicketMessage was not created.');
        $this->assertEquals('ticket_message', $activity->log_name);
        $this->assertEquals('Ticket internal note was created', $activity->description);
        $this->assertEquals($this->agent->id, $activity->causer_id);
        $this->assertEquals($internalNote->id, $activity->subject_id);

        // Verify stored properties
        $attributes = $activity->properties['attributes'] ?? [];

        // 1. Metadata MUST be preserved
        $this->assertEquals($this->ticket->id, $attributes['ticket_id'] ?? null);
        $this->assertEquals($this->agent->id, $attributes['user_id'] ?? null);
        $this->assertEquals(TicketMessageType::INTERNAL_NOTE->value, $attributes['type'] ?? null);

        // 2. Secret internal note content MUST NOT be in properties array or serialized JSON
        $this->assertArrayNotHasKey('content', $attributes, 'Internal note content was stored in activity attributes.');
        $this->assertStringNotContainsString($secretContent, json_encode($activity->properties));

        // 3. Verify Global Activity Log show view does NOT expose the secret content
        $response = $this->get(route('activity-logs.show', $activity->id));
        $response->assertOk();
        $response->assertDontSee($secretContent);
        $response->assertSee(TicketMessageType::INTERNAL_NOTE->value);
        $response->assertSee('Ticket internal note was created');
    }

    /**
     * Requirement: Public replies continue to work and their activity metadata/content are logged appropriately.
     */
    public function test_public_reply_activity_logging_records_content_and_metadata(): void
    {
        $replyContent = 'This is a standard public support reply for customer.';

        $this->actingAs($this->agent);

        $reply = $this->messageService->addReply(
            $this->ticket,
            $this->agent,
            $replyContent
        );

        $this->assertInstanceOf(TicketMessage::class, $reply);
        $this->assertTrue($reply->isReply());

        $activity = ActivityLog::where('subject_type', TicketMessage::class)
            ->where('subject_id', $reply->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals('ticket_message', $activity->log_name);
        $this->assertEquals('Ticket reply was created', $activity->description);

        $attributes = $activity->properties['attributes'] ?? [];
        $this->assertEquals($this->ticket->id, $attributes['ticket_id'] ?? null);
        $this->assertEquals($this->agent->id, $attributes['user_id'] ?? null);
        $this->assertEquals(TicketMessageType::REPLY->value, $attributes['type'] ?? null);
        $this->assertEquals($replyContent, $attributes['content'] ?? null);
    }

    /**
     * Requirement: Existing conversation visibility permissions remain strictly unchanged.
     * Customers cannot see internal notes, while authorized staff can.
     */
    public function test_existing_conversation_visibility_permissions_remain_unchanged(): void
    {
        $publicReplyText = 'Public customer question content.';
        $internalNoteText = 'Agent private discussion note.';

        // 1. Add public reply and internal note
        $this->messageService->addReply($this->ticket, $this->customer, $publicReplyText);
        $this->messageService->addInternalNote($this->ticket, $this->agent, $internalNoteText);

        // 2. Customer conversation retrieval via service must hide internal note
        $customerMessages = $this->messageService->getConversation($this->ticket, $this->customer);
        $contents = collect($customerMessages->items())->pluck('content')->all();
        $this->assertContains($publicReplyText, $contents);
        $this->assertNotContains($internalNoteText, $contents);

        // 3. Customer portal ticket details page must not see internal note
        $customerResponse = $this->actingAs($this->customer)->get(route('customer.tickets.show', $this->ticket));
        $customerResponse->assertOk();
        $customerResponse->assertSee($publicReplyText);
        $customerResponse->assertDontSee($internalNoteText);

        // 4. Staff with view_ticket permission can see the internal note
        $agentResponse = $this->actingAs($this->agent)->get(route('tickets.show', $this->ticket));
        $agentResponse->assertOk();
        $agentResponse->assertSee($publicReplyText);
        $agentResponse->assertSee($internalNoteText);
    }
}

