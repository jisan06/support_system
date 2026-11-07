<?php

namespace Tests\Feature;

use App\Enums\TicketMessageType;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\TicketMessageService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class TicketConversationTest extends TestCase
{
    use DatabaseTransactions, CreatesAdminUser;

    protected User $customer;
    protected Ticket $ticket;
    protected TicketMessageService $messageService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->messageService = app(TicketMessageService::class);

        $this->customer = User::create([
            'name' => 'John Customer',
            'email' => 'customer_' . Str::random(5) . '@example.com',
            'password' => Hash::make('password'),
            'account_type' => User::TYPE_CUSTOMER,
            'status' => 'Active',
            'email_verified_at' => now(),
        ]);

        $this->ticket = Ticket::create([
            'ticket_number' => 'TCK-TEST-1001',
            'customer_id' => $this->customer->id,
            'subject' => 'Need help with billing',
            'status' => 'open',
            'priority' => 'medium',
        ]);
    }

    /**
     * Test: Customer can reply to their own ticket.
     */
    public function test_customer_can_reply_to_their_own_ticket(): void
    {
        $message = $this->messageService->addReply(
            $this->ticket,
            $this->customer,
            'Here is more information about my billing issue.'
        );

        $this->assertInstanceOf(TicketMessage::class, $message);
        $this->assertEquals(TicketMessageType::REPLY, $message->type);
        $this->assertEquals($this->customer->id, $message->user_id);

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->customer->id,
            'type' => TicketMessageType::REPLY->value,
            'content' => 'Here is more information about my billing issue.',
        ]);

        $this->ticket->refresh();
        $this->assertNotNull($this->ticket->last_reply_at);
    }

    /**
     * Test: Customer cannot reply to another customer's ticket.
     */
    public function test_customer_cannot_reply_to_another_customers_ticket(): void
    {
        $otherCustomer = User::create([
            'name' => 'Jane Other',
            'email' => 'other_' . Str::random(5) . '@example.com',
            'password' => Hash::make('password'),
            'account_type' => User::TYPE_CUSTOMER,
            'status' => 'Active',
        ]);

        $this->expectException(AuthorizationException::class);

        $this->messageService->addReply(
            $this->ticket,
            $otherCustomer,
            'I am trying to reply to someone else ticket.'
        );

        $this->assertDatabaseMissing('ticket_messages', [
            'ticket_id' => $this->ticket->id,
            'content' => 'I am trying to reply to someone else ticket.',
        ]);
    }

    /**
     * Test: Authorized agent can reply.
     */
    public function test_authorized_agent_can_reply(): void
    {
        $agent = $this->createUserWithPermissions(['view_ticket', 'reply_ticket']);

        $response = $this->actingAs($agent)
            ->post(route('tickets.reply', $this->ticket->id), [
                'content' => 'Hello, I am looking into your billing issue right now.',
            ]);

        $response->assertRedirect(route('tickets.show', $this->ticket->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $this->ticket->id,
            'user_id' => $agent->id,
            'type' => TicketMessageType::REPLY->value,
            'content' => 'Hello, I am looking into your billing issue right now.',
        ]);
    }

    /**
     * Test: Authorized agent can create an internal note.
     */
    public function test_authorized_agent_can_create_an_internal_note(): void
    {
        $agent = $this->createUserWithPermissions(['view_ticket', 'internal_note_ticket']);

        $response = $this->actingAs($agent)
            ->post(route('tickets.internal_note', $this->ticket->id), [
                'content' => 'Checked Stripe logs: charge ID ch_123 failed due to insufficient funds.',
            ]);

        $response->assertRedirect(route('tickets.show', $this->ticket->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $this->ticket->id,
            'user_id' => $agent->id,
            'type' => TicketMessageType::INTERNAL_NOTE->value,
            'content' => 'Checked Stripe logs: charge ID ch_123 failed due to insufficient funds.',
        ]);
    }

    /**
     * Test: Customer cannot create an internal note.
     */
    public function test_customer_cannot_create_an_internal_note(): void
    {
        // 1. Through HTTP route
        $response = $this->actingAs($this->customer)
            ->post(route('tickets.internal_note', $this->ticket->id), [
                'content' => 'Customer trying to post internal note via route.',
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('ticket_messages', [
            'content' => 'Customer trying to post internal note via route.',
        ]);

        // 2. Through Service Layer directly
        $this->expectException(AuthorizationException::class);
        $this->messageService->addInternalNote($this->ticket, $this->customer, 'Direct service exploit attempt');
    }

    /**
     * Test: Customer cannot see internal notes.
     */
    public function test_customer_cannot_see_internal_notes(): void
    {
        // Create 1 public reply and 1 internal note
        TicketMessage::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->customer->id,
            'type' => TicketMessageType::REPLY,
            'content' => 'Visible customer question content.',
        ]);

        $agent = User::create([
            'name' => 'Agent Smith',
            'email' => 'agent_' . Str::random(5) . '@example.com',
            'password' => Hash::make('password'),
            'account_type' => User::TYPE_ADMIN,
            'status' => 'Active',
        ]);

        TicketMessage::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $agent->id,
            'type' => TicketMessageType::INTERNAL_NOTE,
            'content' => 'CONFIDENTIAL_SECRET_INTERNAL_NOTE_12345',
        ]);

        // Customer conversation service query never returns internal notes
        $conversation = $this->messageService->getConversation($this->ticket, $this->customer);

        $this->assertCount(1, $conversation->items());
        $this->assertEquals('Visible customer question content.', $conversation->items()[0]->content);
        $this->assertNotEquals('CONFIDENTIAL_SECRET_INTERNAL_NOTE_12345', $conversation->items()[0]->content);
    }

    /**
     * Test: Internal users can see internal notes when authorized.
     */
    public function test_internal_users_can_see_internal_notes_when_authorized(): void
    {
        $agent = $this->createUserWithPermissions(['view_ticket']);

        TicketMessage::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->customer->id,
            'type' => TicketMessageType::REPLY,
            'content' => 'Public customer query.',
        ]);

        TicketMessage::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $agent->id,
            'type' => TicketMessageType::INTERNAL_NOTE,
            'content' => 'Internal investigation details for staff only.',
        ]);

        $response = $this->actingAs($agent)
            ->get(route('tickets.show', $this->ticket->id));

        $response->assertStatus(200);
        $response->assertSee('Public customer query.');
        $response->assertSee('Internal investigation details for staff only.');

        // Verify service level also returns both for authorized internal user
        $conversation = $this->messageService->getConversation($this->ticket, $agent);
        $this->assertCount(2, $conversation->items());
    }

    /**
     * Test: Unauthorized users cannot access conversation history.
     */
    public function test_unauthorized_users_cannot_access_conversation_history(): void
    {
        // 1. Unauthenticated guest accessing admin route
        $this->get(route('tickets.show', $this->ticket->id))
            ->assertRedirect(route('login'));

        // 2. Internal user without 'view_ticket' permission cannot access admin ticket view
        $unauthorizedAdmin = $this->createUserWithoutPermissions();
        $this->actingAs($unauthorizedAdmin)
            ->get(route('tickets.show', $this->ticket->id))
            ->assertStatus(403);

        // 3. Different customer cannot access ticket conversation via service
        $otherCustomer = User::create([
            'name' => 'Stranger',
            'email' => 'stranger_' . Str::random(5) . '@example.com',
            'password' => Hash::make('password'),
            'account_type' => User::TYPE_CUSTOMER,
            'status' => 'Active',
        ]);

        $this->expectException(AuthorizationException::class);
        $this->messageService->getConversation($this->ticket, $otherCustomer);
    }
}
