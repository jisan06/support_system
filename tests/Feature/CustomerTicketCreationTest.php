<?php

namespace Tests\Feature;

use App\Enums\TicketMessageType;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class CustomerTicketCreationTest extends TestCase
{
    use DatabaseTransactions, CreatesAdminUser;

    protected User $customer;
    protected User $otherCustomer;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::create([
            'name'              => 'Customer Creator',
            'email'             => 'cust_creator_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_CUSTOMER,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);

        $this->otherCustomer = User::create([
            'name'              => 'Another Customer',
            'email'             => 'cust_other_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_CUSTOMER,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);

        $this->adminUser = $this->createAdminUser(['view_ticket', 'create_ticket']);
    }

    /**
     * Test: Authenticated customer can view the ticket creation page.
     */
    public function test_customer_can_view_ticket_create_page(): void
    {
        $response = $this->actingAs($this->customer)->get(route('customer.tickets.create'));

        $response->assertStatus(200);
        $response->assertSee('Submit a Support Ticket');
        $response->assertSee('Technical Support');
        $response->assertSee('Priority');
    }

    /**
     * Test: Unauthenticated guest cannot view ticket create page and is redirected to login.
     */
    public function test_guest_cannot_view_ticket_create_page(): void
    {
        $response = $this->get(route('customer.tickets.create'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test: Internal admin/staff cannot access customer ticket creation (403 Forbidden).
     */
    public function test_admin_cannot_access_customer_ticket_create(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('customer.tickets.create'));

        $response->assertStatus(403);
    }

    /**
     * Test: Customer can create ticket with valid data, SLA deadlines initialized, and public opening message.
     */
    public function test_customer_can_create_ticket_successfully(): void
    {
        $response = $this->actingAs($this->customer)->post(route('customer.tickets.store'), [
            'subject'     => 'Cannot access my account billing',
            'department'  => 'Billing & Payments',
            'priority'    => 'high',
            'description' => 'I am unable to see my latest invoice in the portal. Please assist.',
        ]);

        $response->assertRedirect(route('customer.dashboard'));
        $response->assertSessionHas('success');

        // Verify ticket in database
        $ticket = Ticket::where('subject', 'Cannot access my account billing')->first();
        $this->assertNotNull($ticket);
        $this->assertEquals($this->customer->id, $ticket->customer_id);
        $this->assertEquals('open', $ticket->status);
        $this->assertEquals('high', $ticket->priority);
        $this->assertEquals('Billing & Payments', $ticket->department);
        $this->assertNull($ticket->assigned_to);

        // Verify SLA deadlines initialized
        $this->assertNotNull($ticket->first_response_due_at);
        $this->assertNotNull($ticket->resolution_due_at);

        // Verify initial message created as a public reply
        $this->assertCount(1, $ticket->messages);
        $initialMessage = $ticket->messages->first();
        $this->assertEquals($this->customer->id, $initialMessage->user_id);
        $this->assertEquals(TicketMessageType::REPLY, $initialMessage->type);
        $this->assertEquals('I am unable to see my latest invoice in the portal. Please assist.', $initialMessage->content);
    }

    /**
     * Test: Customer ownership is always derived from authenticated user (customer_id spoofing ignored).
     */
    public function test_customer_id_is_always_derived_from_authenticated_user(): void
    {
        $response = $this->actingAs($this->customer)->post(route('customer.tickets.store'), [
            'customer_id' => $this->otherCustomer->id, // Attempt to spoof customer_id
            'assigned_to' => $this->adminUser->id,     // Attempt to assign agent
            'subject'     => 'Testing ownership derivation',
            'priority'    => 'medium',
            'description' => 'Testing that customer_id cannot be spoofed.',
        ]);

        $response->assertRedirect(route('customer.dashboard'));

        $ticket = Ticket::where('subject', 'Testing ownership derivation')->first();
        $this->assertNotNull($ticket);
        // Owner must be $this->customer, NOT otherCustomer
        $this->assertEquals($this->customer->id, $ticket->customer_id);
        // Agent assignment from customer must be ignored/null
        $this->assertNull($ticket->assigned_to);
    }

    /**
     * Test: Customer can create ticket with file attachments securely.
     */
    public function test_customer_can_create_ticket_with_attachments(): void
    {
        Storage::fake(config('ticket.attachments.disk', 'local'));

        $file1 = UploadedFile::fake()->create('screenshot.png', 100, 'image/png');
        $file2 = UploadedFile::fake()->create('document.pdf', 200, 'application/pdf');

        $response = $this->actingAs($this->customer)->post(route('customer.tickets.store'), [
            'subject'     => 'Bug with screenshot attached',
            'priority'    => 'low',
            'description' => 'Here are the screenshot and pdf documentation.',
            'attachments' => [$file1, $file2],
        ]);

        $response->assertRedirect(route('customer.dashboard'));

        $ticket = Ticket::where('subject', 'Bug with screenshot attached')->first();
        $this->assertNotNull($ticket);

        $initialMessage = $ticket->messages->first();
        $this->assertNotNull($initialMessage);
        $this->assertCount(2, $initialMessage->media);
    }

    /**
     * Test: Invalid input is rejected with validation errors.
     */
    public function test_invalid_input_is_rejected(): void
    {
        $response = $this->actingAs($this->customer)->post(route('customer.tickets.store'), [
            'subject'     => '',
            'priority'    => 'invalid-priority',
            'description' => '',
        ]);

        $response->assertSessionHasErrors(['subject', 'priority', 'description']);
    }

    /**
     * Test: Internal admin users cannot submit tickets via customer ticket endpoint.
     */
    public function test_admin_cannot_submit_via_customer_ticket_endpoint(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('customer.tickets.store'), [
            'subject'     => 'Admin trying to create customer ticket',
            'priority'    => 'low',
            'description' => 'Should be forbidden by EnsureCustomerAccount middleware.',
        ]);

        $response->assertStatus(403);
    }
}

