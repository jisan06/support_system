<?php

namespace App\Services;

use App\Enums\TicketMessageType;
use App\Models\Media;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Repositories\Contracts\TicketMessageRepositoryInterface;
use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Services\Traits\SoftDeleteTrait;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class TicketMessageService extends BaseService
{
    use SoftDeleteTrait;

    protected TicketRepositoryInterface $ticketRepository;
    protected MediaService $mediaService;

    public function __construct(
        TicketMessageRepositoryInterface $repository,
        TicketRepositoryInterface $ticketRepository,
        MediaService $mediaService
    ) {
        parent::__construct($repository);
        $this->ticketRepository = $ticketRepository;
        $this->mediaService = $mediaService;
    }

    /**
     * Get paginated conversation history for a ticket.
     * Enforces customer ownership and internal note visibility.
     *
     * @throws AuthorizationException
     */
    public function getConversation(Ticket $ticket, User $user, int $perPage = 20): LengthAwarePaginator
    {
        $this->authorizeTicketAccess($ticket, $user);

        return $this->repository->getMessagesForTicket($ticket, $user, $perPage);
    }

    /**
     * Add a public reply to the ticket with optional attachments.
     *
     * @param array<UploadedFile> $attachments
     * @param array<int> $mediaIds
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function addReply(
        Ticket $ticket,
        User $sender,
        string $content,
        array $attachments = [],
        array $mediaIds = [],
        bool $isInitial = false
    ): TicketMessage {
        $this->authorizeTicketAccess($ticket, $sender);

        return $this->transaction(function () use ($ticket, $sender, $content, $attachments, $mediaIds, $isInitial) {
            $message = $this->repository->createMessage($ticket, [
                'user_id' => $sender->id,
                'type'    => TicketMessageType::REPLY->value,
                'content' => $content,
            ]);

            if (!empty($attachments)) {
                $this->storeAttachments($message, $attachments, $sender);
            }

            if (!empty($mediaIds)) {
                $this->attachMediaIds($message, $mediaIds, $sender);
            }

            $ticket->update(['last_reply_at' => now()]);

            if (!$isInitial) {
                app(SlaService::class)->recordFirstResponse($ticket, $sender);

                // Notify customer when an Admin/Agent posts a public reply
                if ($sender->isAdmin() && $ticket->customer_id !== $sender->id) {
                    $customer = $ticket->customer ?? User::find($ticket->customer_id);
                    if ($customer) {
                        notify(
                            $customer,
                            "Support Reply on #{$ticket->ticket_number}",
                            "Support agent {$sender->name} replied to your ticket.",
                            'info',
                            route('customer.tickets.show', $ticket)
                        );
                    }
                }
            }

            return $message;
        });
    }

    /**
     * Add an internal note to the ticket with optional attachments.
     * Customers are strictly prohibited from creating internal notes.
     *
     * @param array<UploadedFile> $attachments
     * @param array<int> $mediaIds
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function addInternalNote(Ticket $ticket, User $sender, string $content, array $attachments = [], array $mediaIds = []): TicketMessage
    {
        // Enforce account_type: Only internal staff can add internal notes
        if (!$sender->isAdmin()) {
            throw new AuthorizationException('Customers are not permitted to create internal notes.');
        }

        return $this->transaction(function () use ($ticket, $sender, $content, $attachments, $mediaIds) {
            $message = $this->repository->createMessage($ticket, [
                'user_id' => $sender->id,
                'type'    => TicketMessageType::INTERNAL_NOTE->value,
                'content' => $content,
            ]);

            if (!empty($attachments)) {
                $this->storeAttachments($message, $attachments, $sender);
            }

            if (!empty($mediaIds)) {
                $this->attachMediaIds($message, $mediaIds, $sender);
            }

            return $message;
        });
    }

    /**
     * Securely store attachments for a ticket message on a non-public storage disk.
     * Ensures clean rollback and file deletion on failure to avoid orphaned files.
     *
     * @param array<UploadedFile> $attachments
     * @throws Throwable
     */
    protected function storeAttachments(TicketMessage $message, array $attachments, User $sender): void
    {
        $storedMedia = [];
        $disk = config('ticket.attachments.disk', 'local');
        $collection = config('ticket.attachments.collection', 'ticket_attachments');

        try {
            foreach ($attachments as $index => $file) {
                if ($file instanceof UploadedFile && $file->isValid()) {
                    $media = $this->mediaService->upload(
                        $file,
                        TicketMessage::class,
                        $message->id,
                        $collection,
                        $index,
                        $disk,
                        $sender->id
                    );
                    $storedMedia[] = $media;
                }
            }
        } catch (Throwable $e) {
            // Delete any physical files stored during this failed attempt
            foreach ($storedMedia as $media) {
                Storage::disk($media->disk)->delete($media->path);
            }
            throw $e;
        }
    }

    /**
     * Attach media library items to a ticket message.
     * Creates a dedicated private attachment record on the non-public disk
     * while preserving the original item in the Media Library.
     *
     * @param array<int> $mediaIds
     */
    protected function attachMediaIds(TicketMessage $message, array $mediaIds, User $sender): void
    {
        $privateDisk = config('ticket.attachments.disk', 'local');
        $collection = config('ticket.attachments.collection', 'ticket_attachments');
        $allowedExtensions = config('ticket.attachments.allowed_extensions', ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip']);

        $mediaItems = Media::whereIn('id', $mediaIds)->get();

        foreach ($mediaItems as $media) {
            $ext = strtolower(pathinfo($media->file_name, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExtensions, true)) {
                continue;
            }

            // Copy file to the private non-public disk under ticket_attachments collection
            $securePath = config('media.path_prefix', 'media') . '/' . $collection . '/' . \Illuminate\Support\Str::random(40) . '.' . $ext;
            if (Storage::disk($media->disk)->exists($media->path)) {
                Storage::disk($privateDisk)->put($securePath, Storage::disk($media->disk)->get($media->path));
            } else {
                $securePath = $media->path;
            }

            // Create private attachment record for this ticket message
            // Original Media Library record and its public file remain completely intact!
            Media::create([
                'mediable_type' => TicketMessage::class,
                'mediable_id'   => $message->id,
                'collection'    => $collection,
                'file_name'     => $media->file_name,
                'disk'          => $privateDisk,
                'path'          => $securePath,
                'mime_type'     => $media->mime_type,
                'size'          => $media->size,
                'sort_order'    => 0,
                'created_by'    => $sender->id,
            ]);
        }
    }

    /**
     * Securely download or view an attachment associated with a ticket message.
     *
     * @throws AuthorizationException
     */
    public function downloadAttachment(Ticket $ticket, TicketMessage $message, Media $media, User $user): StreamedResponse
    {
        // 1. Structural integrity check: message must belong to ticket
        if ((int) $message->ticket_id !== (int) $ticket->id) {
            throw new AuthorizationException('Message does not belong to the requested ticket.');
        }

        // 2. Structural integrity check: media must belong to message
        if ($media->mediable_type !== TicketMessage::class || (int) $media->mediable_id !== (int) $message->id) {
            throw new AuthorizationException('Attachment does not belong to the requested message.');
        }

        // 3. User authorization:
        // Customers can ONLY download attachments from their OWN tickets AND NEVER from internal notes
        if ($user->isCustomer()) {
            if ((int) $ticket->customer_id !== (int) $user->id) {
                throw new AuthorizationException('You are not authorized to access attachments from another customer\'s ticket.');
            }

            if ($message->isInternalNote()) {
                throw new AuthorizationException('Customers are not permitted to access internal note attachments.');
            }
        }

        // 4. File existence verification on disk
        $disk = $media->disk;
        if (!Storage::disk($disk)->exists($media->path)) {
            abort(404, 'Attachment file not found.');
        }

        // 5. Secure streamed response: view inline in browser for images/PDFs, download for other files
        $size = Storage::disk($disk)->size($media->path);

        if (str_starts_with($media->mime_type, 'image/') || $media->mime_type === 'application/pdf') {
            return response()->stream(function () use ($disk, $media) {
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }
                $stream = Storage::disk($disk)->readStream($media->path);
                fpassthru($stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }, 200, [
                'Content-Type'        => $media->mime_type,
                'Content-Length'      => (string) $size,
                'Content-Disposition' => 'inline; filename="' . $media->file_name . '"',
            ]);
        }

        return response()->streamDownload(function () use ($disk, $media) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            $stream = Storage::disk($disk)->readStream($media->path);
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, $media->file_name, [
            'Content-Type'   => $media->mime_type,
            'Content-Length' => (string) $size,
        ]);
    }

    /**
     * Authorize access to a ticket.
     *
     * @throws AuthorizationException
     */
    protected function authorizeTicketAccess(Ticket $ticket, User $user): void
    {
        if ($user->isCustomer() && (int) $ticket->customer_id !== (int) $user->id) {
            throw new AuthorizationException('You are not authorized to access this ticket.');
        }
    }
}
