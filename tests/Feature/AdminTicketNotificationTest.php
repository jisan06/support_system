<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketMessageService;
use App\Services\TicketService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class AdminTicketNotificationTest extends TestCase
{
    use DatabaseTransactions, CreatesAdminUser;

    protected User $customer;
    protected User $otherCustomer;
    protected User $authorizedAdmin;
    protected TicketService $ticketService;
    protected TicketMessageService $messageService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ticketService = app(TicketService::class);
        $this->messageService = app(TicketMessageService::class);

        $this->customer = User::create([
            'name'              => 'Customer Jane',
            'email'             => 'jane_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_CUSTOMER,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);

        $this->otherCustomer = User::create([
            'name'              => 'Customer Other',
            'email'             => 'other_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_CUSTOMER,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);

        $this->authorizedAdmin = $this->createAdminWithPermissions(['view_ticket', 'edit_ticket', 'reply_ticket', 'view_notification']);
    }

    /**
     * Helper to create an admin user with specific permissions.
     */
    protected function createAdminWithPermissions(array $permissions, string $status = 'Active'): User
    {
        $role = Role::create([
            'name'   => 'Admin Role ' . Str::random(6),
            'status' => 'Active',
        ]);

        foreach ($permissions as $permission) {
            RolePermission::create([
                'role_id' => $role->id,
                'slug'    => $permission,
            ]);
        }

        $admin = User::create([
            'name'              => 'Admin ' . Str::random(6),
            'email'             => 'admin_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_ADMIN,
            'status'            => $status,
            'email_verified_at' => now(),
        ]);

        $admin->roles()->attach($role->id, ['created_by' => $admin->id]);

        return $admin;
    }

    /**
     * Helper: Create ticket owned by customer.
     */
    protected function createTicketForCustomer(User $customer, string $status = 'open'): Ticket
    {
        return Ticket::create([
            'ticket_number' => 'TCK-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
            'customer_id'   => $customer->id,
            'subject'       => 'Test Ticket ' . Str::random(5),
            'status'        => $status,
            'priority'      => 'medium',
            'last_reply_at' => now(),
        ]);
    }

    /**
     * Test 1: Admin-side notification when a customer creates a ticket.
     */
    public function test_admin_receives_notification_when_customer_creates_ticket(): void
    {
        $response = $this->actingAs($this->customer)->post(route('customer.tickets.store'), [
            'subject'     => 'New Issue by Customer',
            'department'  => 'Technical Support',
            'priority'    => 'high',
            'description' => 'Detailed problem description here.',
        ]);

        $response->assertRedirect(route('customer.dashboard'));

        $ticket = Ticket::where('subject', 'New Issue by Customer')->first();
        $this->assertNotNull($ticket);

        // Verify authorized admin received the notification
        $this->assertEquals(1, $this->authorizedAdmin->notifications()->count());

        $notification = $this->authorizedAdmin->notifications()->first();
        $this->assertEquals('New Ticket Created', $notification->data['title']);
        $this->assertStringContainsString($ticket->ticket_number, $notification->data['message']);
        $this->assertStringContainsString($this->customer->name, $notification->data['message']);
        $this->assertEquals(route('tickets.show', $ticket->id), $notification->data['url']);
        $this->assertNull($notification->read_at);
    }

    /**
     * Test 2: Admin-side notification when a customer sends a public reply.
     */
    public function test_admin_receives_notification_when_customer_sends_public_reply(): void
    {
        $ticket = $this->createTicketForCustomer($this->customer);

        $response = $this->actingAs($this->customer)->post(route('customer.tickets.reply', $ticket->id), [
            'content' => 'Customer follow-up response.',
        ]);

        $response->assertRedirect(route('customer.tickets.show', $ticket->id));

        // Verify authorized admin received the notification
        $this->assertEquals(1, $this->authorizedAdmin->notifications()->count());

        $notification = $this->authorizedAdmin->notifications()->first();
        $this->assertEquals('New Customer Reply', $notification->data['title']);
        $this->assertStringContainsString($ticket->ticket_number, $notification->data['message']);
        $this->assertStringContainsString($this->customer->name, $notification->data['message']);
        $this->assertEquals(route('tickets.show', $ticket->id), $notification->data['url']);
        $this->assertNull($notification->read_at);
    }

    /**
     * Test 3: Internal notes do not trigger notifications.
     */
    public function test_internal_notes_do_not_trigger_customer_reply_notifications(): void
    {
        $ticket = $this->createTicketForCustomer($this->customer);

        $this->messageService->addInternalNote(
            $ticket,
            $this->authorizedAdmin,
            'Confidential internal note among staff.'
        );

        $this->assertEquals(0, $this->authorizedAdmin->notifications()->count());
    }

    /**
     * Test 4: Admin/agent replies do not trigger customer-reply notifications.
     */
    public function test_admin_replies_do_not_trigger_customer_reply_notifications(): void
    {
        $secondAdmin = $this->createAdminWithPermissions(['view_ticket']);
        $ticket = $this->createTicketForCustomer($this->customer);

        $this->messageService->addReply(
            $ticket,
            $this->authorizedAdmin,
            'Support agent reply to customer.'
        );

        // Neither admin should receive a "New Customer Reply" notification
        $this->assertEquals(0, $this->authorizedAdmin->notifications()->count());
        $this->assertEquals(0, $secondAdmin->notifications()->count());

        // The customer should receive their support reply notification
        $this->assertEquals(1, $this->customer->notifications()->count());
    }

    /**
     * Test 5: Customer accounts do not receive admin-side notifications.
     */
    public function test_customer_accounts_do_not_receive_admin_side_notifications(): void
    {
        $ticket = $this->createTicketForCustomer($this->customer);

        // Customer replies
        $this->messageService->addReply($ticket, $this->customer, 'Customer reply text');

        // Other customer has 0 notifications
        $this->assertEquals(0, $this->otherCustomer->notifications()->count());
        // The sender customer has 0 notifications for their own reply
        $this->assertEquals(0, $this->customer->notifications()->count());
    }

    /**
     * Test 6: Inactive admins and admins without view_ticket permission are not notified.
     */
    public function test_inactive_admins_and_unauthorized_admins_do_not_receive_notifications(): void
    {
        $inactiveAdmin = $this->createAdminWithPermissions(['view_ticket'], 'Inactive');
        $unauthorizedAdmin = $this->createAdminWithPermissions(['view_media']); // no view_ticket

        $this->actingAs($this->customer)->post(route('customer.tickets.store'), [
            'subject'     => 'Testing Recipient Filtering',
            'department'  => 'Technical Support',
            'priority'    => 'low',
            'description' => 'Only active admins with view_ticket should be notified.',
        ]);

        $this->assertEquals(1, $this->authorizedAdmin->notifications()->count());
        $this->assertEquals(0, $inactiveAdmin->notifications()->count());
        $this->assertEquals(0, $unauthorizedAdmin->notifications()->count());
    }

    /**
     * Test 7: Notification link points to Admin ticket details page and open marks as read.
     */
    public function test_notification_links_to_admin_ticket_details_and_open_marks_as_read(): void
    {
        $ticket = $this->createTicketForCustomer($this->customer);

        $this->messageService->addReply($ticket, $this->customer, 'Customer response');

        $notification = $this->authorizedAdmin->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertEquals(route('tickets.show', $ticket->id), $notification->data['url']);

        // Opening the notification marks it as read and redirects to destination
        $response = $this->actingAs($this->authorizedAdmin)->get(route('notifications.open', $notification->id));

        $response->assertRedirect(route('tickets.show', $ticket->id));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    /**
     * Test 8: Existing customer notifications continue working properly.
     */
    public function test_existing_customer_notifications_continue_working(): void
    {
        $ticket = $this->createTicketForCustomer($this->customer, 'open');

        // Status update notifies customer
        $this->ticketService->updateStatus($ticket, 'resolved');

        $this->assertEquals(1, $this->customer->notifications()->count());
        $custNoti = $this->customer->notifications()->first();
        $this->assertStringContainsString('Resolved', $custNoti->data['title']);
        $this->assertEquals(route('customer.tickets.show', $ticket), $custNoti->data['url']);
    }

    /**
     * Test 9: One agent's public reply marks notifications for that ticket read for ALL internal users.
     * Handles both "New Ticket Created" and "New Customer Reply" notifications.
     */
    public function test_agent_public_reply_marks_ticket_notifications_read_for_all_internal_users(): void
    {
        $secondAdmin = $this->createAdminWithPermissions(['view_ticket', 'reply_ticket']);

        // 1. Customer creates ticket -> Both admins receive "New Ticket Created"
        $this->actingAs($this->customer)->post(route('customer.tickets.store'), [
            'subject'     => 'Global Read Lifecycle Ticket',
            'department'  => 'Technical Support',
            'priority'    => 'medium',
            'description' => 'Need help with setup.',
        ]);

        $ticket = Ticket::where('subject', 'Global Read Lifecycle Ticket')->first();
        $this->assertNotNull($ticket);

        // 2. Customer sends a public reply -> Both admins receive "New Customer Reply"
        $this->actingAs($this->customer)->post(route('customer.tickets.reply', $ticket->id), [
            'content' => 'Additional customer info.',
        ]);

        // Verify both admins have 2 unread notifications for this ticket
        $this->assertEquals(2, $this->authorizedAdmin->unreadNotifications()->count());
        $this->assertEquals(2, $secondAdmin->unreadNotifications()->count());

        // 3. Authorized Admin A sends a public reply to the ticket
        $this->actingAs($this->authorizedAdmin)->post(route('tickets.reply', $ticket->id), [
            'content' => 'Hello, I have resolved your setup issue.',
        ]);

        // 4. Assert all notifications for this ticket are marked as read for ALL internal users
        $this->assertEquals(0, $this->authorizedAdmin->unreadNotifications()->count());
        $this->assertEquals(0, $secondAdmin->unreadNotifications()->count());

        // All 4 notifications still exist (not deleted) but have read_at populated
        $this->assertEquals(2, $this->authorizedAdmin->notifications()->whereNotNull('read_at')->count());
        $this->assertEquals(2, $secondAdmin->notifications()->whereNotNull('read_at')->count());
    }

    /**
     * Test 10: Public reply on Ticket A does NOT affect notifications of Ticket B.
     */
    public function test_agent_public_reply_does_not_affect_other_tickets_notifications(): void
    {
        // Customer creates Ticket A
        $this->actingAs($this->customer)->post(route('customer.tickets.store'), [
            'subject'     => 'Ticket Alpha',
            'department'  => 'General Inquiry',
            'priority'    => 'low',
            'description' => 'Alpha description',
        ]);
        $ticketA = Ticket::where('subject', 'Ticket Alpha')->first();

        // Customer creates Ticket B
        $this->actingAs($this->customer)->post(route('customer.tickets.store'), [
            'subject'     => 'Ticket Beta',
            'department'  => 'General Inquiry',
            'priority'    => 'low',
            'description' => 'Beta description',
        ]);
        $ticketB = Ticket::where('subject', 'Ticket Beta')->first();

        // Admin has 2 unread notifications (1 for A, 1 for B)
        $this->assertEquals(2, $this->authorizedAdmin->unreadNotifications()->count());

        // Agent replies to Ticket A
        $this->actingAs($this->authorizedAdmin)->post(route('tickets.reply', $ticketA->id), [
            'content' => 'Reply to Ticket Alpha',
        ]);

        // Unread count must be 1 (Ticket B remains unread)
        $this->assertEquals(1, $this->authorizedAdmin->unreadNotifications()->count());

        $unreadTicketB = $this->authorizedAdmin->unreadNotifications()->first();
        $this->assertStringContainsString($ticketB->ticket_number, $unreadTicketB->data['message']);
    }

    /**
     * Test 11: Internal notes do NOT mark admin notifications as read.
     */
    public function test_internal_note_does_not_mark_notifications_as_read(): void
    {
        $this->actingAs($this->customer)->post(route('customer.tickets.store'), [
            'subject'     => 'Internal Note Test Ticket',
            'department'  => 'Technical Support',
            'priority'    => 'medium',
            'description' => 'Initial inquiry',
        ]);
        $ticket = Ticket::where('subject', 'Internal Note Test Ticket')->first();

        $this->assertEquals(1, $this->authorizedAdmin->unreadNotifications()->count());

        // Agent adds an internal note
        $this->messageService->addInternalNote($ticket, $this->authorizedAdmin, 'Staff discussion note.');

        // Notification must remain unread
        $this->assertEquals(1, $this->authorizedAdmin->unreadNotifications()->count());
    }

    /**
     * Test 12: Customer replies do NOT trigger automatic read handling.
     */
    public function test_customer_replies_do_not_trigger_automatic_read_handling(): void
    {
        $this->actingAs($this->customer)->post(route('customer.tickets.store'), [
            'subject'     => 'Customer Reply No Read Test',
            'department'  => 'Technical Support',
            'priority'    => 'medium',
            'description' => 'Initial inquiry',
        ]);
        $ticket = Ticket::where('subject', 'Customer Reply No Read Test')->first();

        $this->assertEquals(1, $this->authorizedAdmin->unreadNotifications()->count());

        // Customer posts a reply
        $this->actingAs($this->customer)->post(route('customer.tickets.reply', $ticket->id), [
            'content' => 'Customer reply should add notification, not mark previous as read.',
        ]);

        // Unread count must increase to 2 (creation notification + customer reply notification, neither read)
        $this->assertEquals(2, $this->authorizedAdmin->unreadNotifications()->count());
    }

    /**
     * Test 13: Agent public reply does not affect customer-side notifications.
     */
    public function test_agent_public_reply_does_not_affect_customer_side_notifications(): void
    {
        $ticket = $this->createTicketForCustomer($this->customer, 'open');

        // Give customer an unread status notification
        $this->ticketService->updateStatus($ticket, 'in_progress');
        $this->assertEquals(1, $this->customer->unreadNotifications()->count());

        // Agent replies to ticket
        $this->actingAs($this->authorizedAdmin)->post(route('tickets.reply', $ticket->id), [
            'content' => 'Public agent reply',
        ]);

        // Customer now has the previous unread status notification PLUS the agent reply notification (2 unread)
        // Their notifications were NOT marked as read
        $this->assertEquals(2, $this->customer->unreadNotifications()->count());
    }
}

