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

    /**
     * Test 10: Search by ticket number and subject.
     */
    public function test_listing_searches_by_ticket_number_and_subject(): void
    {
        $targetNumberTicket = Ticket::create([
            'ticket_number' => 'TCK-SPECIAL-1234',
            'customer_id'   => $this->customer->id,
            'subject'       => 'Generic inquiry text',
            'status'        => 'open',
            'priority'      => 'medium',
            'last_reply_at' => now(),
        ]);

        $targetSubjectTicket = Ticket::create([
            'ticket_number' => 'TCK-GENERIC-5678',
            'customer_id'   => $this->customer->id,
            'subject'       => 'Quantum Database Corruption Incident',
            'status'        => 'open',
            'priority'      => 'high',
            'last_reply_at' => now(),
        ]);

        $unrelatedTicket = Ticket::create([
            'ticket_number' => 'TCK-NORMAL-9999',
            'customer_id'   => $this->customer->id,
            'subject'       => 'Normal Routine Ticket',
            'status'        => 'open',
            'priority'      => 'low',
            'last_reply_at' => now(),
        ]);

        // 1. Search by ticket number
        $searchByNumber = $this->actingAs($this->customer)->get(route('customer.tickets.index', ['search' => 'SPECIAL-1234']));
        $searchByNumber->assertStatus(200);
        $searchByNumber->assertSee('TCK-SPECIAL-1234');
        $searchByNumber->assertDontSee('Quantum Database Corruption Incident');
        $searchByNumber->assertDontSee('Normal Routine Ticket');

        // 2. Search by subject keyword
        $searchBySubject = $this->actingAs($this->customer)->get(route('customer.tickets.index', ['search' => 'Quantum Database']));
        $searchBySubject->assertStatus(200);
        $searchBySubject->assertSee('Quantum Database Corruption Incident');
        $searchBySubject->assertDontSee('TCK-SPECIAL-1234');
        $searchBySubject->assertDontSee('Normal Routine Ticket');
    }

    /**
     * Test 11: Filter by department.
     */
    public function test_listing_filters_by_department(): void
    {
        $billingTicket = Ticket::create([
            'ticket_number' => 'TCK-DEPT-BILL-' . strtoupper(Str::random(3)),
            'customer_id'   => $this->customer->id,
            'subject'       => 'Invoice Billing Query',
            'department'    => 'Billing & Payments',
            'status'        => 'open',
            'priority'      => 'medium',
            'last_reply_at' => now(),
        ]);

        $techTicket = Ticket::create([
            'ticket_number' => 'TCK-DEPT-TECH-' . strtoupper(Str::random(3)),
            'customer_id'   => $this->customer->id,
            'subject'       => 'Server Error 500 Incident',
            'department'    => 'Technical Support',
            'status'        => 'open',
            'priority'      => 'high',
            'last_reply_at' => now(),
        ]);

        $response = $this->actingAs($this->customer)->get(route('customer.tickets.index', ['department' => 'Billing & Payments']));
        $response->assertStatus(200);
        $response->assertSee($billingTicket->ticket_number);
        $response->assertSee('Invoice Billing Query');
        $response->assertDontSee($techTicket->ticket_number);
        $response->assertDontSee('Server Error 500 Incident');
    }

    /**
     * Test 12: Filter by date range (date_from and date_to).
     */
    public function test_listing_filters_by_date_range(): void
    {
        $oldTicket = Ticket::create([
            'ticket_number' => 'TCK-DATE-OLD-' . strtoupper(Str::random(3)),
            'customer_id'   => $this->customer->id,
            'subject'       => 'Old Past Month Ticket',
            'status'        => 'resolved',
            'priority'      => 'low',
            'created_at'    => now()->subDays(20),
            'last_reply_at' => now()->subDays(20),
        ]);

        $recentTicket = Ticket::create([
            'ticket_number' => 'TCK-DATE-RECENT-' . strtoupper(Str::random(3)),
            'customer_id'   => $this->customer->id,
            'subject'       => 'Recent This Week Ticket',
            'status'        => 'open',
            'priority'      => 'medium',
            'created_at'    => now()->subDays(2),
            'last_reply_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($this->customer)->get(route('customer.tickets.index', [
            'date_from' => now()->subDays(5)->toDateString(),
            'date_to'   => now()->toDateString(),
        ]));

        $response->assertStatus(200);
        $response->assertSee($recentTicket->ticket_number);
        $response->assertSee('Recent This Week Ticket');
        $response->assertDontSee($oldTicket->ticket_number);
        $response->assertDontSee('Old Past Month Ticket');
    }

    /**
     * Test 13: Pagination preserves active search and filter query parameters.
     */
    public function test_pagination_preserves_active_filter_and_search_parameters(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            Ticket::create([
                'ticket_number' => sprintf('TCK-FLT-PAG-%02d', $i),
                'customer_id'   => $this->customer->id,
                'subject'       => "Filtered Pagination Item {$i}",
                'department'    => 'Technical Support',
                'status'        => 'open',
                'priority'      => 'high',
                'last_reply_at' => now()->subMinutes($i),
            ]);
        }

        $response = $this->actingAs($this->customer)->get(route('customer.tickets.index', [
            'department' => 'Technical Support',
            'priority'   => 'high',
            'search'     => 'FilteredPagination',
            'page'       => 1,
        ]));

        $response->assertStatus(200);
        // Verify page link includes preserved query string parameters
        $response->assertSee('priority=high');
        $response->assertSee('search=FilteredPagination');
        $response->assertSee('department=Technical');
    }

    /**
     * Test 14: Customer search never reveals other customers' tickets.
     */
    public function test_customer_search_cannot_find_other_customers_tickets(): void
    {
        $otherCustomerSecretTicket = Ticket::create([
            'ticket_number' => 'TCK-SECRET-9999',
            'customer_id'   => $this->otherCustomer->id,
            'subject'       => 'Super Secret Confidential Keyword',
            'department'    => 'Billing & Payments',
            'status'        => 'open',
            'priority'      => 'urgent',
            'last_reply_at' => now(),
        ]);

        $response = $this->actingAs($this->customer)->get(route('customer.tickets.index', [
            'search' => 'TCK-SECRET-9999',
        ]));

        $response->assertStatus(200);
        $response->assertDontSee('Super Secret Confidential Keyword');
        $response->assertSee('No Support Tickets Found');
    }

    /**
     * Test 15: Validation rejects date_from greater than date_to.
     */
    public function test_validation_rejects_date_from_greater_than_date_to(): void
    {
        $response = $this->actingAs($this->customer)->get(route('customer.tickets.index', [
            'date_from' => '2026-12-01',
            'date_to'   => '2026-11-01',
        ]));

        $response->assertRedirect(route('customer.tickets.index'));
        $response->assertSessionHasErrors(['date_to']);
    }

    /**
     * Test 16: Validation rejects malformed date strings.
     */
    public function test_validation_rejects_malformed_date_format(): void
    {
        $response = $this->actingAs($this->customer)->get(route('customer.tickets.index', [
            'date_from' => 'not-a-valid-date',
        ]));

        $response->assertRedirect(route('customer.tickets.index'));
        $response->assertSessionHasErrors(['date_from']);
    }

    /**
     * Test 17: Validation rejects invalid status and priority parameters.
     */
    public function test_validation_rejects_invalid_status_and_priority(): void
    {
        $invalidStatus = $this->actingAs($this->customer)->get(route('customer.tickets.index', [
            'status' => 'arbitrary_invalid_status',
        ]));
        $invalidStatus->assertRedirect(route('customer.tickets.index'));
        $invalidStatus->assertSessionHasErrors(['status']);

        $invalidPriority = $this->actingAs($this->customer)->get(route('customer.tickets.index', [
            'priority' => 'hacked_priority',
        ]));
        $invalidPriority->assertRedirect(route('customer.tickets.index'));
        $invalidPriority->assertSessionHasErrors(['priority']);
    }

    /**
     * Test 18: Repository safely handles invalid filters and preserves admin caller behavior.
     */
    public function test_repository_defensive_querying_and_admin_compatibility(): void
    {
        $repo = app(\App\Repositories\Contracts\TicketRepositoryInterface::class);

        // 1. Calling with invalid status ignores the status and does not crash
        $tickets = $repo->getTicketsForUser($this->customer, ['status' => 'unknown_hack']);
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $tickets);

        // 2. Calling with date_from > date_to returns empty paginator safely
        $tickets = $repo->getTicketsForUser($this->customer, ['date_from' => '2026-12-01', 'date_to' => '2026-11-01']);
        $this->assertEquals(0, $tickets->total());

        // 3. Calling with an integer as 2nd parameter (Admin controller legacy signature) works
        $adminTickets = $repo->getTicketsForUser($this->adminUser, 15);
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $adminTickets);
    }
}

