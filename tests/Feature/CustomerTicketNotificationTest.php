<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketMessageService;
use App\Services\TicketService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class CustomerTicketNotificationTest extends TestCase
{
    use DatabaseTransactions, CreatesAdminUser;

    protected User $customer;
    protected User $otherCustomer;
    protected User $adminUser;
    protected TicketService $ticketService;
    protected TicketMessageService $messageService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ticketService = app(TicketService::class);
        $this->messageService = app(TicketMessageService::class);

        $this->customer = User::create([
            'name'              => 'Alice Customer',
            'email'             => 'alice_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_CUSTOMER,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);

        $this->otherCustomer = User::create([
            'name'              => 'Bob Other',
            'email'             => 'bob_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_CUSTOMER,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);

        $this->adminUser = $this->createAdminUser(['view_ticket', 'reply_ticket', 'create_ticket', 'update_ticket']);
    }

    protected function createTicketForCustomer(User $customer, string $status = 'open'): Ticket
    {
        return Ticket::create([
            'ticket_number' => 'TCK-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
            'customer_id'   => $customer->id,
            'subject'       => 'Connection reset by peer',
            'status'        => $status,
            'priority'      => 'high',
            'last_reply_at' => now(),
        ]);
    }

    /**
     * Test 1: Admin public reply creates a notification for the ticket customer.
     */
    public function test_admin_public_reply_creates_notification_for_customer(): void
    {
        $ticket = $this->createTicketForCustomer($this->customer);

        $this->messageService->addReply(
            $ticket,
            $this->adminUser,
            'We are reviewing your connection logs now.'
        );

        $this->assertEquals(1, $this->customer->notifications()->count());

        $notification = $this->customer->notifications()->first();
        $this->assertStringContainsString($ticket->ticket_number, $notification->data['title']);
        $this->assertEquals(route('customer.tickets.show', $ticket), $notification->data['url']);
        $this->assertNull($notification->read_at);
    }

    /**
     * Test 2: Customer reply to their own ticket does not notify themselves.
     */
    public function test_customer_reply_to_own_ticket_does_not_create_notification_for_themselves(): void
    {
        $ticket = $this->createTicketForCustomer($this->customer);

        $this->messageService->addReply(
            $ticket,
            $this->customer,
            'Here is additional info about the issue.'
        );

        $this->assertEquals(0, $this->customer->notifications()->count());
    }

    /**
     * Test 3: Admin internal notes never notify the customer.
     */
    public function test_admin_internal_note_never_notifies_customer(): void
    {
        $ticket = $this->createTicketForCustomer($this->customer);

        $this->messageService->addInternalNote(
            $ticket,
            $this->adminUser,
            'This is an internal staff note discussing server logs.'
        );

        $this->assertEquals(0, $this->customer->notifications()->count());
    }

    /**
     * Test 4: Ticket status update notifies the customer.
     */
    public function test_ticket_status_change_creates_notification_for_customer(): void
    {
        $ticket = $this->createTicketForCustomer($this->customer, 'open');

        $this->ticketService->updateStatus($ticket, 'in_progress');

        $this->assertEquals(1, $this->customer->notifications()->count());

        $notification = $this->customer->notifications()->first();
        $this->assertStringContainsString('Status Updated', $notification->data['title']);
        $this->assertEquals(route('customer.tickets.show', $ticket), $notification->data['url']);
    }

    /**
     * Test 5: Resolving ticket creates a success notification for the customer.
     */
    public function test_ticket_resolved_creates_success_notification(): void
    {
        $ticket = $this->createTicketForCustomer($this->customer, 'open');

        $this->ticketService->updateStatus($ticket, 'resolved');

        $this->assertEquals(1, $this->customer->notifications()->count());

        $notification = $this->customer->notifications()->first();
        $this->assertStringContainsString('Resolved', $notification->data['title']);
        $this->assertEquals('success', $notification->data['type']);
        $this->assertEquals(route('customer.tickets.show', $ticket), $notification->data['url']);
    }

    /**
     * Test 6: Closing ticket creates a notification for the customer.
     */
    public function test_ticket_closed_creates_notification(): void
    {
        $ticket = $this->createTicketForCustomer($this->customer, 'open');

        $this->ticketService->updateStatus($ticket, 'closed');

        $this->assertEquals(1, $this->customer->notifications()->count());

        $notification = $this->customer->notifications()->first();
        $this->assertStringContainsString('Closed', $notification->data['title']);
        $this->assertEquals(route('customer.tickets.show', $ticket), $notification->data['url']);
    }

    /**
     * Test 7: Notifications are strictly sent only to the ticket owner.
     */
    public function test_notifications_sent_only_to_ticket_owner(): void
    {
        $ticket = $this->createTicketForCustomer($this->customer, 'open');

        $this->messageService->addReply(
            $ticket,
            $this->adminUser,
            'Direct reply to customer Alice.'
        );

        $this->assertEquals(1, $this->customer->notifications()->count());
        $this->assertEquals(0, $this->otherCustomer->notifications()->count());
    }

    /**
     * Test 8: Customer can view their notifications list.
     */
    public function test_customer_can_view_notification_list(): void
    {
        $ticket = $this->createTicketForCustomer($this->customer, 'open');
        $this->ticketService->updateStatus($ticket, 'resolved');

        $response = $this->actingAs($this->customer)->get('/customer/notifications');

        $response->assertOk();
        $response->assertViewIs('customer.notifications.index');
        $response->assertSee('Ticket Notifications');
        $response->assertSee($ticket->ticket_number);
    }

    /**
     * Test 9: Customer can mark a single notification as read.
     */
    public function test_customer_can_mark_notification_as_read(): void
    {
        $ticket = $this->createTicketForCustomer($this->customer, 'open');
        $this->ticketService->updateStatus($ticket, 'resolved');

        $notification = $this->customer->unreadNotifications()->first();
        $this->assertNotNull($notification);

        $response = $this->actingAs($this->customer)
            ->patch('/customer/notifications/' . $notification->id . '/read');

        $response->assertRedirect();

        $this->assertEquals(0, $this->customer->unreadNotifications()->count());
        $this->assertNotNull($notification->fresh()->read_at);
    }

    /**
     * Test 10: Customer can mark all notifications as read.
     */
    public function test_customer_can_mark_all_notifications_as_read(): void
    {
        $ticket1 = $this->createTicketForCustomer($this->customer, 'open');
        $ticket2 = $this->createTicketForCustomer($this->customer, 'open');

        $this->ticketService->updateStatus($ticket1, 'in_progress');
        $this->ticketService->updateStatus($ticket2, 'resolved');

        $this->assertEquals(2, $this->customer->unreadNotifications()->count());

        $response = $this->actingAs($this->customer)
            ->post('/customer/notifications/mark-all-read');

        $response->assertRedirect();

        $this->assertEquals(0, $this->customer->unreadNotifications()->count());
    }

    /**
     * Test 11: Customer cannot mark another customer's notification as read.
     */
    public function test_customer_cannot_mark_another_customers_notification_as_read(): void
    {
        $ticket = $this->createTicketForCustomer($this->customer, 'open');
        $this->ticketService->updateStatus($ticket, 'resolved');

        $notification = $this->customer->unreadNotifications()->first();

        // Other customer tries to mark Alice's notification as read
        $response = $this->actingAs($this->otherCustomer)
            ->patch('/customer/notifications/' . $notification->id . '/read');

        $response->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
    }

    /**
     * Test 12: Opening a notification marks it as read and redirects to the ticket.
     */
    public function test_opening_notification_marks_as_read_and_redirects_to_ticket(): void
    {
        $ticket = $this->createTicketForCustomer($this->customer, 'open');
        $this->ticketService->updateStatus($ticket, 'resolved');

        $notification = $this->customer->unreadNotifications()->first();

        $response = $this->actingAs($this->customer)
            ->get('/customer/notifications/' . $notification->id . '/open');

        $response->assertRedirect(route('customer.tickets.show', $ticket));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    /**
     * Test 13: Guests cannot access customer notifications.
     */
    public function test_guest_cannot_access_customer_notifications(): void
    {
        $response = $this->get('/customer/notifications');
        $response->assertRedirect('/login');

        $postResponse = $this->post('/customer/notifications/mark-all-read');
        $postResponse->assertRedirect('/login');
    }
}
