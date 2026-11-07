<?php

namespace Tests\Feature;

use App\Enums\TicketMessageType;
use App\Models\Media;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class CustomerTicketListingAndDetailsTest extends TestCase
{
    use DatabaseTransactions, CreatesAdminUser;

    protected User $customer;
    protected User $otherCustomer;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::create([
            'name'              => 'Jane Customer',
            'email'             => 'cust_jane_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_CUSTOMER,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);

        $this->otherCustomer = User::create([
            'name'              => 'Bob Other Customer',
            'email'             => 'cust_bob_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_CUSTOMER,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);

        $this->adminUser = $this->createAdminUser(['view_ticket', 'create_ticket']);
    }

    /**
     * Test 1: Customer can view ticket listing page with their tickets.
     */
    public function test_customer_can_view_ticket_listing_with_sla_and_details(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TCK-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
            'customer_id'   => $this->customer->id,
            'subject'       => 'Checkout gateway timeout error',
            'status'        => 'open',
            'priority'      => 'high',
            'department'    => 'Billing & Payments',
            'last_reply_at' => now(),
        ]);

        $response = $this->actingAs($this->customer)->get(route('customer.tickets.index'));

        $response->assertStatus(200);
        $response->assertSee('My Support Tickets');
        $response->assertSee($ticket->ticket_number);
        $response->assertSee('Checkout gateway timeout error');
        $response->assertSee('Billing & Payments');
        $response->assertSee('SLA:');
    }

    /**
     * Test 2: Listing displays ONLY tickets belonging to authenticated customer.
     */
    public function test_listing_only_displays_authenticated_customer_tickets(): void
    {
        $myTicket = Ticket::create([
            'ticket_number' => 'TCK-MY-' . strtoupper(Str::random(4)),
            'customer_id'   => $this->customer->id,
            'subject'       => 'My Private Ticket Subject',
            'status'        => 'open',
            'priority'      => 'medium',
            'last_reply_at' => now(),
        ]);

        $otherTicket = Ticket::create([
            'ticket_number' => 'TCK-OTHER-' . strtoupper(Str::random(4)),
            'customer_id'   => $this->otherCustomer->id,
            'subject'       => 'Other Customer Secret Problem',
            'status'        => 'open',
            'priority'      => 'medium',
            'last_reply_at' => now(),
        ]);

        $response = $this->actingAs($this->customer)->get(route('customer.tickets.index'));

        $response->assertStatus(200);
        $response->assertSee($myTicket->ticket_number);
        $response->assertSee('My Private Ticket Subject');
        $response->assertDontSee($otherTicket->ticket_number);
        $response->assertDontSee('Other Customer Secret Problem');
    }

    /**
     * Test 3: Basic filtering by status works.
     */
    public function test_listing_filters_by_status(): void
    {
        $openTicket = Ticket::create([
            'ticket_number' => 'TCK-OPEN-' . strtoupper(Str::random(4)),
            'customer_id'   => $this->customer->id,
            'subject'       => 'Still Open Problem',
            'status'        => 'open',
            'priority'      => 'medium',
            'last_reply_at' => now(),
        ]);

        $resolvedTicket = Ticket::create([
            'ticket_number' => 'TCK-RESOLVED-' . strtoupper(Str::random(4)),
            'customer_id'   => $this->customer->id,
            'subject'       => 'Already Solved Ticket',
            'status'        => 'resolved',
            'priority'      => 'medium',
            'last_reply_at' => now(),
        ]);

        $response = $this->actingAs($this->customer)->get(route('customer.tickets.index', ['status' => 'resolved']));

        $response->assertStatus(200);
        $response->assertSee($resolvedTicket->ticket_number);
        $response->assertSee('Already Solved Ticket');
        $response->assertDontSee($openTicket->ticket_number);
        $response->assertDontSee('Still Open Problem');
    }

    /**
     * Test 4: Basic filtering by priority works.
     */
    public function test_listing_filters_by_priority(): void
    {
        $urgentTicket = Ticket::create([
            'ticket_number' => 'TCK-URGENT-' . strtoupper(Str::random(4)),
            'customer_id'   => $this->customer->id,
            'subject'       => 'Production Outage Critical',
            'status'        => 'open',
            'priority'      => 'urgent',
            'last_reply_at' => now(),
        ]);

        $lowTicket = Ticket::create([
            'ticket_number' => 'TCK-LOW-' . strtoupper(Str::random(4)),
            'customer_id'   => $this->customer->id,
            'subject'       => 'Minor Typo in Docs',
            'status'        => 'open',
            'priority'      => 'low',
            'last_reply_at' => now(),
        ]);

        $response = $this->actingAs($this->customer)->get(route('customer.tickets.index', ['priority' => 'urgent']));

        $response->assertStatus(200);
        $response->assertSee($urgentTicket->ticket_number);
        $response->assertSee('Production Outage Critical');
        $response->assertDontSee($lowTicket->ticket_number);
        $response->assertDontSee('Minor Typo in Docs');
    }

    /**
     * Test 5: Pagination works for customer tickets.
     */
    public function test_listing_pagination(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            Ticket::create([
                'ticket_number' => sprintf('TCK-PAG-%02d-%s', $i, strtoupper(Str::random(3))),
                'customer_id'   => $this->customer->id,
                'subject'       => "Batch Ticket Item {$i}",
                'status'        => 'open',
                'priority'      => 'medium',
                'last_reply_at' => now()->subMinutes($i),
            ]);
        }

        $page1 = $this->actingAs($this->customer)->get(route('customer.tickets.index', ['page' => 1]));
        $page1->assertStatus(200);
        $page1->assertSee('Batch Ticket Item 1');

        $page2 = $this->actingAs($this->customer)->get(route('customer.tickets.index', ['page' => 2]));
        $page2->assertStatus(200);
        $page2->assertSee('Batch Ticket Item 12');
    }

    /**
     * Test 6: Customer can view their own ticket details and public conversation.
     */
    public function test_customer_can_view_own_ticket_details(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TCK-VIEW-' . strtoupper(Str::random(4)),
            'customer_id'   => $this->customer->id,
            'subject'       => 'How to reset two-factor auth',
            'status'        => 'open',
            'priority'      => 'medium',
            'department'    => 'Account & Access',
            'last_reply_at' => now(),
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $this->customer->id,
            'type'      => TicketMessageType::REPLY->value,
            'content'   => 'Initial customer inquiry: I lost my phone.',
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $this->adminUser->id,
            'type'      => TicketMessageType::REPLY->value,
            'content'   => 'Agent public reply: We can help you reset 2FA.',
        ]);

        $response = $this->actingAs($this->customer)->get(route('customer.tickets.show', $ticket->id));

        $response->assertStatus(200);
        $response->assertSee($ticket->ticket_number);
        $response->assertSee('How to reset two-factor auth');
        $response->assertSee('Initial customer inquiry: I lost my phone.');
        $response->assertSee('Agent public reply: We can help you reset 2FA.');
        $response->assertSee('Account & Access');
    }

    /**
     * Test 7: Backend ownership enforcement - customer cannot access other customer's ticket.
     */
    public function test_customer_cannot_access_other_customers_ticket(): void
    {
        $otherTicket = Ticket::create([
            'ticket_number' => 'TCK-FORBIDDEN-' . strtoupper(Str::random(4)),
            'customer_id'   => $this->otherCustomer->id,
            'subject'       => 'Confidential Financial Report Issue',
            'status'        => 'open',
            'priority'      => 'high',
            'last_reply_at' => now(),
        ]);

        $response = $this->actingAs($this->customer)->get(route('customer.tickets.show', $otherTicket->id));

        $response->assertStatus(403);
    }

    /**
     * Test 8: Internal notes and internal-only attachments are NEVER exposed to customers.
     */
    public function test_customer_cannot_see_internal_notes_or_download_internal_attachments(): void
    {
        Storage::fake('local');

        $ticket = Ticket::create([
            'ticket_number' => 'TCK-PRIVACY-' . strtoupper(Str::random(4)),
            'customer_id'   => $this->customer->id,
            'subject'       => 'Billing Discrepancy Inquiry',
            'status'        => 'open',
            'priority'      => 'high',
            'last_reply_at' => now(),
        ]);

        // Public opening reply
        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $this->customer->id,
            'type'      => TicketMessageType::REPLY->value,
            'content'   => 'Public customer question: I was overcharged by $20.',
        ]);

        // Staff internal note (Strictly confidential)
        $internalNote = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $this->adminUser->id,
            'type'      => TicketMessageType::INTERNAL_NOTE->value,
            'content'   => 'SECRET_INTERNAL_NOTE: Check stripe charge ID #ch_123456 before refunding.',
        ]);

        // Attach confidential media to the internal note
        $fakeFile = UploadedFile::fake()->create('confidential_audit.pdf', 100, 'application/pdf');
        $storedPath = $fakeFile->store('media/ticket_attachments', 'local');
        $internalMedia = Media::create([
            'mediable_type' => TicketMessage::class,
            'mediable_id'   => $internalNote->id,
            'collection'    => 'ticket_attachments',
            'file_name'     => 'confidential_audit.pdf',
            'disk'          => 'local',
            'path'          => $storedPath,
            'mime_type'     => 'application/pdf',
            'size'          => 102400,
            'sort_order'    => 0,
            'created_by'    => $this->adminUser->id,
        ]);

        // 1. Visit ticket details as the customer
        $response = $this->actingAs($this->customer)->get(route('customer.tickets.show', $ticket->id));

        $response->assertStatus(200);
        $response->assertSee('Public customer question: I was overcharged by $20.');
        $response->assertDontSee('SECRET_INTERNAL_NOTE: Check stripe charge ID #ch_123456 before refunding.');
        $response->assertDontSee('confidential_audit.pdf');

        // 2. Direct malicious URL attempt to download the internal note attachment
        $downloadAttempt = $this->actingAs($this->customer)->get(
            route('customer.tickets.attachments.show', [$ticket->id, $internalNote->id, $internalMedia->id])
        );

        $downloadAttempt->assertStatus(403);
    }

    /**
     * Test 9: Customer can post a reply to their open ticket.
     */
    public function test_customer_can_reply_to_their_ticket(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TCK-REPLY-' . strtoupper(Str::random(4)),
            'customer_id'   => $this->customer->id,
            'subject'       => 'Need update on account recovery',
            'status'        => 'open',
            'priority'      => 'medium',
            'last_reply_at' => now(),
        ]);

        $response = $this->actingAs($this->customer)->post(route('customer.tickets.reply', $ticket->id), [
            'content' => 'Here is my additional information regarding the account.',
        ]);

        $response->assertRedirect(route('customer.tickets.show', $ticket->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'user_id'   => $this->customer->id,
            'type'      => TicketMessageType::REPLY->value,
            'content'   => 'Here is my additional information regarding the account.',
        ]);
    }
}

