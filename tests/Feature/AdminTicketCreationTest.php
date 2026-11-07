<?php

namespace Tests\Feature;

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

class AdminTicketCreationTest extends TestCase
{
    use DatabaseTransactions, CreatesAdminUser;

    protected User $customer;
    protected User $agent;
    protected string $disk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->disk = config('ticket.attachments.disk', 'local');
        Storage::fake($this->disk);

        $this->customer = User::create([
            'name' => 'Support Customer',
            'email' => 'customer_' . Str::random(8) . '@example.com',
            'password' => Hash::make('password123'),
            'account_type' => User::TYPE_CUSTOMER,
            'status' => 'Active',
            'email_verified_at' => now(),
        ]);

        $this->agent = User::create([
            'name' => 'Support Agent',
            'email' => 'agent_' . Str::random(8) . '@example.com',
            'password' => Hash::make('password123'),
            'account_type' => User::TYPE_ADMIN,
            'status' => 'Active',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Test: Authorized user with create_ticket permission can view the ticket creation page with breadcrumbs.
     */
    public function test_authorized_user_can_view_create_ticket_page(): void
    {
        $admin = $this->createAdminUser(['create_ticket', 'view_ticket']);

        $response = $this->actingAs($admin)->get(route('tickets.create'));

        $response->assertStatus(200);
        $response->assertSee('Create Ticket');
        $response->assertSee($this->customer->name);
        // Breadcrumb checks
        $response->assertSee('Admin Dashboard');
        $response->assertSee('Support');
        $response->assertSee('Tickets');
    }

    /**
     * Test: Unauthorized user without create_ticket permission is forbidden.
     */
    public function test_unauthorized_user_cannot_view_create_ticket_page(): void
    {
        $admin = $this->createAdminUser(['view_ticket']); // missing create_ticket

        $response = $this->actingAs($admin)->get(route('tickets.create'));

        $response->assertStatus(403);
    }

    /**
     * Test: Authorized admin/agent can manually create a ticket.
     */
    public function test_authorized_admin_can_create_ticket(): void
    {
        $admin = $this->createAdminUser(['create_ticket', 'view_ticket']);

        $payload = [
            'customer_id' => $this->customer->id,
            'assigned_to' => $this->agent->id,
            'subject' => 'Payment gateway integration failed',
            'department' => 'Billing & Payments',
            'priority' => 'high',
            'description' => 'The customer cannot complete checkout using Stripe payment gateway.',
        ];

        $response = $this->actingAs($admin)->post(route('tickets.store'), $payload);

        $ticket = Ticket::where('subject', 'Payment gateway integration failed')->first();

        $this->assertNotNull($ticket);
        $this->assertEquals($this->customer->id, $ticket->customer_id);
        $this->assertEquals($this->agent->id, $ticket->assigned_to);
        $this->assertEquals('Billing & Payments', $ticket->department);
        $this->assertEquals('high', $ticket->priority);
        $this->assertEquals('open', $ticket->status);
        $this->assertStringStartsWith('TCK-', $ticket->ticket_number);

        // Check first message (initial description)
        $message = TicketMessage::where('ticket_id', $ticket->id)->first();
        $this->assertNotNull($message);
        $this->assertEquals('The customer cannot complete checkout using Stripe payment gateway.', $message->content);
        $this->assertEquals($admin->id, $message->user_id);
        $this->assertEquals('reply', $message->type->value);

        $response->assertRedirect(route('tickets.show', $ticket));
        $response->assertSessionHas('success');
    }

    /**
     * Test: Ticket creation with file attachments.
     */
    public function test_ticket_creation_with_attachments(): void
    {
        $admin = $this->createAdminUser(['create_ticket', 'view_ticket']);

        $file = UploadedFile::fake()->create('error_log.txt', 25, 'text/plain');

        $payload = [
            'customer_id' => $this->customer->id,
            'subject' => 'Ticket with attachment',
            'priority' => 'medium',
            'description' => 'Attached is the diagnostic log.',
            'attachments' => [$file],
        ];

        $response = $this->actingAs($admin)->post(route('tickets.store'), $payload);

        $ticket = Ticket::where('subject', 'Ticket with attachment')->first();
        $this->assertNotNull($ticket);

        $message = TicketMessage::where('ticket_id', $ticket->id)->first();
        $this->assertNotNull($message);

        $this->assertCount(1, $message->media);
        $media = $message->media->first();
        $this->assertEquals('error_log.txt', $media->file_name);
        Storage::disk($this->disk)->assertExists($media->path);

        $response->assertRedirect(route('tickets.show', $ticket));
    }

    /**
     * Test: Ticket creation with existing Media Library selection.
     */
    public function test_ticket_creation_with_media_library_attachment(): void
    {
        $admin = $this->createAdminUser(['create_ticket', 'view_ticket']);

        // Create an unassigned media item on the public disk (as created by MediaPicker)
        $publicDisk = 'public';
        Storage::fake($publicDisk);
        Storage::disk($publicDisk)->put('media/default/screenshot.png', 'fake image content');

        $media = Media::create([
            'mediable_type' => Media::class,
            'mediable_id'   => 0,
            'collection'    => 'default',
            'file_name'     => 'screenshot.png',
            'disk'          => $publicDisk,
            'path'          => 'media/default/screenshot.png',
            'mime_type'     => 'image/png',
            'size'          => 1024,
            'created_by'    => $admin->id,
        ]);

        $payload = [
            'customer_id' => $this->customer->id,
            'subject'     => 'Ticket with Media Library attachment',
            'priority'    => 'low',
            'description' => 'Attached image from Media Library.',
            'media_ids'   => [$media->id],
        ];

        $response = $this->actingAs($admin)->post(route('tickets.store'), $payload);

        $ticket = Ticket::where('subject', 'Ticket with Media Library attachment')->first();
        $this->assertNotNull($ticket);

        $message = TicketMessage::where('ticket_id', $ticket->id)->first();
        $this->assertNotNull($message);

        // Check message has the attachment secured on the ticket disk
        $this->assertCount(1, $message->media);
        $attachedMedia = $message->media->first();
        $this->assertEquals(TicketMessage::class, $attachedMedia->mediable_type);
        $this->assertEquals($message->id, $attachedMedia->mediable_id);
        $this->assertEquals('ticket_attachments', $attachedMedia->collection);
        $this->assertEquals('screenshot.png', $attachedMedia->file_name);
        $this->assertEquals($this->disk, $attachedMedia->disk);
        Storage::disk($this->disk)->assertExists($attachedMedia->path);

        // Original media in Media Library remains intact
        $freshMedia = $media->fresh();
        $this->assertEquals(Media::class, $freshMedia->mediable_type);
        $this->assertEquals('default', $freshMedia->collection);

        $response->assertRedirect(route('tickets.show', $ticket));
    }

    /**
     * Test: Validation errors for missing required fields.
     */
    public function test_validation_fails_for_missing_required_fields(): void
    {
        $admin = $this->createAdminUser(['create_ticket']);

        $response = $this->actingAs($admin)->post(route('tickets.store'), []);

        $response->assertSessionHasErrors(['customer_id', 'subject', 'priority', 'description']);
    }

    /**
     * Test: Validation fails when customer_id does not belong to a customer account.
     */
    public function test_validation_fails_if_customer_id_is_not_customer_account(): void
    {
        $admin = $this->createAdminUser(['create_ticket']);

        // Passing an admin user ID as customer_id
        $response = $this->actingAs($admin)->post(route('tickets.store'), [
            'customer_id' => $this->agent->id,
            'subject' => 'Invalid customer test',
            'priority' => 'low',
            'description' => 'Test body',
        ]);

        $response->assertSessionHasErrors(['customer_id']);
    }

    /**
     * Test: Unauthorized user without create_ticket cannot store ticket.
     */
    public function test_unauthorized_user_cannot_store_ticket(): void
    {
        $admin = $this->createAdminUser(['view_ticket']);

        $response = $this->actingAs($admin)->post(route('tickets.store'), [
            'customer_id' => $this->customer->id,
            'subject' => 'Unauthorized ticket',
            'priority' => 'low',
            'description' => 'Test body',
        ]);

        $response->assertStatus(403);
    }
}
