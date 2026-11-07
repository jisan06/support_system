<?php

namespace Tests\Feature;

use App\Enums\TicketMessageType;
use App\Models\Media;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\TicketMessageService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class TicketAttachmentTest extends TestCase
{
    use DatabaseTransactions, CreatesAdminUser;

    protected User $customer;
    protected Ticket $ticket;
    protected TicketMessageService $messageService;
    protected string $disk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->disk = config('ticket.attachments.disk', 'local');
        Storage::fake($this->disk);

        $this->messageService = app(TicketMessageService::class);

        $this->customer = User::create([
            'name' => 'Attachment Customer',
            'email' => 'customer_' . Str::random(6) . '@example.com',
            'password' => Hash::make('password'),
            'account_type' => User::TYPE_CUSTOMER,
            'status' => 'Active',
            'email_verified_at' => now(),
        ]);

        $this->ticket = Ticket::create([
            'ticket_number' => 'TCK-ATT-' . strtoupper(Str::random(6)),
            'customer_id' => $this->customer->id,
            'subject' => 'Issue with system logs and invoice',
            'status' => 'open',
            'priority' => 'medium',
        ]);
    }

    /**
     * Test: Valid attachment upload on public reply.
     */
    public function test_valid_attachment_upload_on_reply(): void
    {
        $agent = $this->createUserWithPermissions(['view_ticket', 'reply_ticket']);
        $file = UploadedFile::fake()->create('invoice.pdf', 500, 'application/pdf');

        $response = $this->actingAs($agent)
            ->post(route('tickets.reply', $this->ticket->id), [
                'content'     => 'Here is your requested invoice document.',
                'attachments' => [$file],
            ]);

        $response->assertRedirect(route('tickets.show', $this->ticket->id));
        $response->assertSessionHas('success');

        $message = TicketMessage::where('ticket_id', $this->ticket->id)
            ->where('type', TicketMessageType::REPLY->value)
            ->latest('id')
            ->first();

        $this->assertNotNull($message);
        $this->assertCount(1, $message->media);

        $media = $message->media->first();
        $this->assertEquals('invoice.pdf', $media->file_name);
        $this->assertEquals($this->disk, $media->disk);
        $this->assertEquals('application/pdf', $media->mime_type);
        Storage::disk($this->disk)->assertExists($media->path);
    }

    /**
     * Test: Public reply with media library item attachment.
     */
    public function test_public_reply_with_media_library_item(): void
    {
        $agent = $this->createUserWithPermissions(['view_ticket', 'reply_ticket']);

        $publicDisk = 'public';
        Storage::fake($publicDisk);
        Storage::disk($publicDisk)->put('media/default/doc.png', 'fake image bytes');

        $media = Media::create([
            'mediable_type' => Media::class,
            'mediable_id'   => 0,
            'collection'    => 'default',
            'file_name'     => 'doc.png',
            'disk'          => $publicDisk,
            'path'          => 'media/default/doc.png',
            'mime_type'     => 'image/png',
            'size'          => 2048,
            'created_by'    => $agent->id,
        ]);

        $response = $this->actingAs($agent)
            ->post(route('tickets.reply', $this->ticket->id), [
                'content'   => 'Attaching document from media library.',
                'media_ids' => [$media->id],
            ]);

        $response->assertRedirect(route('tickets.show', $this->ticket->id));

        $message = TicketMessage::where('ticket_id', $this->ticket->id)
            ->where('type', TicketMessageType::REPLY->value)
            ->latest('id')
            ->first();

        $this->assertCount(1, $message->media);
        $attached = $message->media->first();
        $this->assertEquals('ticket_attachments', $attached->collection);
        $this->assertEquals($this->disk, $attached->disk);
        Storage::disk($this->disk)->assertExists($attached->path);

        // Original media in library remains untouched
        $this->assertEquals('default', $media->fresh()->collection);
    }

    /**
     * Test: Internal note with media library item attachment.
     */
    public function test_internal_note_with_media_library_item(): void
    {
        $agent = $this->createUserWithPermissions(['view_ticket', 'internal_note_ticket']);

        $publicDisk = 'public';
        Storage::fake($publicDisk);
        Storage::disk($publicDisk)->put('media/default/log.txt', 'error trace content');

        $media = Media::create([
            'mediable_type' => Media::class,
            'mediable_id'   => 0,
            'collection'    => 'default',
            'file_name'     => 'log.txt',
            'disk'          => $publicDisk,
            'path'          => 'media/default/log.txt',
            'mime_type'     => 'text/plain',
            'size'          => 512,
            'created_by'    => $agent->id,
        ]);

        $response = $this->actingAs($agent)
            ->post(route('tickets.internal_note', $this->ticket->id), [
                'content'   => 'Attaching log from media library.',
                'media_ids' => [$media->id],
            ]);

        $response->assertRedirect(route('tickets.show', $this->ticket->id));

        $message = TicketMessage::where('ticket_id', $this->ticket->id)
            ->where('type', TicketMessageType::INTERNAL_NOTE->value)
            ->latest('id')
            ->first();

        $this->assertCount(1, $message->media);
        $attached = $message->media->first();
        $this->assertEquals('ticket_attachments', $attached->collection);
        $this->assertEquals($this->disk, $attached->disk);
    }

    /**
     * Test: Valid attachment upload on internal note.
     */
    public function test_valid_attachment_upload_on_internal_note(): void
    {
        $agent = $this->createUserWithPermissions(['view_ticket', 'internal_note_ticket']);
        $file = UploadedFile::fake()->create('server_error.log.txt', 200, 'text/plain');

        $response = $this->actingAs($agent)
            ->post(route('tickets.internal_note', $this->ticket->id), [
                'content'     => 'Found trace logs in server console.',
                'attachments' => [$file],
            ]);

        $response->assertRedirect(route('tickets.show', $this->ticket->id));
        $response->assertSessionHas('success');

        $message = TicketMessage::where('ticket_id', $this->ticket->id)
            ->where('type', TicketMessageType::INTERNAL_NOTE->value)
            ->latest('id')
            ->first();

        $this->assertNotNull($message);
        $this->assertCount(1, $message->media);

        $media = $message->media->first();
        $this->assertEquals('server_error.log.txt', $media->file_name);
        Storage::disk($this->disk)->assertExists($media->path);
    }

    /**
     * Test: Multiple attachments can be uploaded simultaneously.
     */
    public function test_multiple_attachments_upload(): void
    {
        $agent = $this->createUserWithPermissions(['view_ticket', 'reply_ticket']);

        $file1 = UploadedFile::fake()->image('screenshot1.png');
        $file2 = UploadedFile::fake()->create('document.pdf', 300, 'application/pdf');

        $response = $this->actingAs($agent)
            ->post(route('tickets.reply', $this->ticket->id), [
                'content'     => 'Providing screenshot and doc.',
                'attachments' => [$file1, $file2],
            ]);

        $response->assertRedirect();

        $message = TicketMessage::where('ticket_id', $this->ticket->id)
            ->latest('id')
            ->first();

        $this->assertCount(2, $message->media);
    }

    /**
     * Test: Rejection of invalid / dangerous file types.
     */
    public function test_invalid_file_type_rejection(): void
    {
        $agent = $this->createUserWithPermissions(['view_ticket', 'reply_ticket']);
        $badFile = UploadedFile::fake()->create('script.exe', 100, 'application/x-msdownload');

        $response = $this->actingAs($agent)
            ->post(route('tickets.reply', $this->ticket->id), [
                'content'     => 'Trying to upload an exe file.',
                'attachments' => [$badFile],
            ]);

        $response->assertSessionHasErrors('attachments.0');
    }

    /**
     * Test: Rejection of files exceeding maximum allowed size.
     */
    public function test_maximum_file_size_validation(): void
    {
        $agent = $this->createUserWithPermissions(['view_ticket', 'reply_ticket']);
        $maxSizeKb = config('ticket.attachments.max_size_kb', 10240);

        // Create file exceeding configured limit
        $oversizedFile = UploadedFile::fake()->create('huge_file.pdf', $maxSizeKb + 1024, 'application/pdf');

        $response = $this->actingAs($agent)
            ->post(route('tickets.reply', $this->ticket->id), [
                'content'     => 'Uploading an oversized attachment.',
                'attachments' => [$oversizedFile],
            ]);

        $response->assertSessionHasErrors('attachments.0');
    }

    /**
     * Test: Authorized internal user can download ticket attachment.
     */
    public function test_authorized_attachment_download(): void
    {
        $agent = $this->createUserWithPermissions(['view_ticket']);

        $message = TicketMessage::create([
            'ticket_id' => $this->ticket->id,
            'user_id'   => $this->customer->id,
            'type'      => TicketMessageType::REPLY,
            'content'   => 'Here is my bill file.',
        ]);

        $path = 'ticket-attachments/2026/04/test_bill.pdf';
        Storage::disk($this->disk)->put($path, 'fake pdf content');

        $media = Media::create([
            'mediable_type' => TicketMessage::class,
            'mediable_id'   => $message->id,
            'collection'    => 'ticket_attachments',
            'file_name'     => 'test_bill.pdf',
            'disk'          => $this->disk,
            'path'          => $path,
            'mime_type'     => 'application/pdf',
            'size'          => 1234,
            'created_by'    => $this->customer->id,
        ]);

        $response = $this->actingAs($agent)
            ->get(route('tickets.attachments.download', [$this->ticket->id, $message->id, $media->id]));

        $response->assertStatus(200);
        $this->assertTrue($response->headers->get('content-disposition') !== null);
    }

    /**
     * Test: Image attachments return inline content-disposition for viewing in new tab.
     */
    public function test_image_attachment_can_be_viewed_inline_in_new_tab(): void
    {
        $agent = $this->createUserWithPermissions(['view_ticket']);

        $message = TicketMessage::create([
            'ticket_id' => $this->ticket->id,
            'user_id'   => $this->customer->id,
            'type'      => TicketMessageType::REPLY,
            'content'   => 'Here is the bug screenshot.',
        ]);

        $path = 'ticket_attachments/bug.png';
        Storage::disk($this->disk)->put($path, 'fake png content');

        $media = Media::create([
            'mediable_type' => TicketMessage::class,
            'mediable_id'   => $message->id,
            'collection'    => 'ticket_attachments',
            'file_name'     => 'bug.png',
            'disk'          => $this->disk,
            'path'          => $path,
            'mime_type'     => 'image/png',
            'size'          => 4567,
            'created_by'    => $this->customer->id,
        ]);

        $response = $this->actingAs($agent)
            ->get(route('tickets.attachments.download', [$this->ticket->id, $message->id, $media->id]));

        $response->assertStatus(200);
        $this->assertStringContainsString('inline', (string) $response->headers->get('content-disposition'));
        $this->assertEquals('image/png', $response->headers->get('content-type'));
    }

    /**
     * Test: Unauthorized guest cannot download attachment.
     */
    public function test_unauthorized_guest_cannot_download_attachment(): void
    {
        $message = TicketMessage::create([
            'ticket_id' => $this->ticket->id,
            'user_id'   => $this->customer->id,
            'type'      => TicketMessageType::REPLY,
            'content'   => 'Public message with file.',
        ]);

        $media = Media::create([
            'mediable_type' => TicketMessage::class,
            'mediable_id'   => $message->id,
            'collection'    => 'ticket_attachments',
            'file_name'     => 'file.pdf',
            'disk'          => $this->disk,
            'path'          => 'path/to/file.pdf',
            'mime_type'     => 'application/pdf',
            'size'          => 500,
        ]);

        $response = $this->get(route('tickets.attachments.download', [$this->ticket->id, $message->id, $media->id]));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test: Customer ownership isolation - customer cannot download attachment from another customer's ticket.
     */
    public function test_customer_cannot_download_other_customer_attachment(): void
    {
        $otherCustomer = User::create([
            'name' => 'Other Customer',
            'email' => 'other_' . Str::random(6) . '@example.com',
            'password' => Hash::make('password'),
            'account_type' => User::TYPE_CUSTOMER,
            'status' => 'Active',
        ]);

        $message = TicketMessage::create([
            'ticket_id' => $this->ticket->id,
            'user_id'   => $this->customer->id,
            'type'      => TicketMessageType::REPLY,
            'content'   => 'Secret customer document.',
        ]);

        $media = Media::create([
            'mediable_type' => TicketMessage::class,
            'mediable_id'   => $message->id,
            'collection'    => 'ticket_attachments',
            'file_name'     => 'confidential.pdf',
            'disk'          => $this->disk,
            'path'          => 'path/to/confidential.pdf',
            'mime_type'     => 'application/pdf',
            'size'          => 500,
        ]);

        $this->expectException(AuthorizationException::class);
        $this->messageService->downloadAttachment($this->ticket, $message, $media, $otherCustomer);
    }

    /**
     * Test: Internal note attachment privacy - customer cannot access internal-note attachment even on own ticket.
     */
    public function test_customer_cannot_download_internal_note_attachment(): void
    {
        $internalMessage = TicketMessage::create([
            'ticket_id' => $this->ticket->id,
            'user_id'   => 1,
            'type'      => TicketMessageType::INTERNAL_NOTE,
            'content'   => 'Staff internal note with confidential attachment.',
        ]);

        $media = Media::create([
            'mediable_type' => TicketMessage::class,
            'mediable_id'   => $internalMessage->id,
            'collection'    => 'ticket_attachments',
            'file_name'     => 'internal_investigation.pdf',
            'disk'          => $this->disk,
            'path'          => 'path/to/internal.pdf',
            'mime_type'     => 'application/pdf',
            'size'          => 800,
        ]);

        $this->expectException(AuthorizationException::class);
        $this->messageService->downloadAttachment($this->ticket, $internalMessage, $media, $this->customer);
    }

    /**
     * Test: Message, ticket, and media IDs cannot be mixed to access unrelated files.
     */
    public function test_mixed_ids_cross_access_is_rejected(): void
    {
        $otherTicket = Ticket::create([
            'ticket_number' => 'TCK-OTHER-99',
            'customer_id'   => $this->customer->id,
            'subject'       => 'Other ticket',
            'status'        => 'open',
            'priority'      => 'low',
        ]);

        $message = TicketMessage::create([
            'ticket_id' => $this->ticket->id,
            'user_id'   => $this->customer->id,
            'type'      => TicketMessageType::REPLY,
            'content'   => 'Message on original ticket.',
        ]);

        $media = Media::create([
            'mediable_type' => TicketMessage::class,
            'mediable_id'   => $message->id,
            'collection'    => 'ticket_attachments',
            'file_name'     => 'test.pdf',
            'disk'          => $this->disk,
            'path'          => 'path/test.pdf',
            'mime_type'     => 'application/pdf',
            'size'          => 100,
        ]);

        // Attempt 1: Message does not belong to ticket ($otherTicket with $message)
        try {
            $this->messageService->downloadAttachment($otherTicket, $message, $media, $this->customer);
            $this->fail('Expected AuthorizationException for mismatched ticket and message.');
        } catch (AuthorizationException $e) {
            $this->assertStringContainsString('Message does not belong to the requested ticket', $e->getMessage());
        }

        // Attempt 2: Media does not belong to message
        $otherMessage = TicketMessage::create([
            'ticket_id' => $this->ticket->id,
            'user_id'   => $this->customer->id,
            'type'      => TicketMessageType::REPLY,
            'content'   => 'Second message.',
        ]);

        try {
            $this->messageService->downloadAttachment($this->ticket, $otherMessage, $media, $this->customer);
            $this->fail('Expected AuthorizationException for media not belonging to message.');
        } catch (AuthorizationException $e) {
            $this->assertStringContainsString('Attachment does not belong to the requested message', $e->getMessage());
        }
    }

    /**
     * Test: Storage cleanup on failure avoids leaving orphaned files.
     */
    public function test_file_cleanup_on_transaction_failure(): void
    {
        $file = UploadedFile::fake()->create('doc_will_fail.pdf', 300, 'application/pdf');

        // We simulate a failure by passing an invalid sender ID or mocking failure
        try {
            // Using a non-existent customer to trigger DB exception on ticket last_reply_at or similar
            $invalidSender = new User();
            $invalidSender->id = 99999999;
            $invalidSender->account_type = User::TYPE_ADMIN;

            $this->messageService->addReply($this->ticket, $invalidSender, 'Triggering rollback test', [$file]);
        } catch (\Throwable $e) {
            // Ensure no files remain in fake storage
            $allFiles = Storage::disk($this->disk)->allFiles();
            $this->assertEmpty($allFiles, 'Failed upload left orphaned files on disk.');
        }
    }
}

