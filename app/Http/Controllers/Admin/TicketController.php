<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TicketInternalNoteRequest;
use App\Http\Requests\TicketReplyRequest;
use App\Models\Media;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Services\TicketMessageService;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketController extends Controller
{
    protected TicketService $ticketService;
    protected TicketMessageService $messageService;

    public function __construct(TicketService $ticketService, TicketMessageService $messageService)
    {
        $this->ticketService = $ticketService;
        $this->messageService = $messageService;
    }

    /**
     * Display a listing of tickets in the admin panel.
     */
    public function index(): View
    {
        $tickets = $this->ticketService->getTicketsForUser(auth()->user(), 15);

        return view('admin.ticket.index', compact('tickets'));
    }

    /**
     * Display the specified ticket with complete conversation history (public replies and internal notes).
     */
    public function show(Ticket $ticket): View
    {
        $ticket->load(['customer', 'assignedTo']);
        $messages = $this->messageService->getConversation($ticket, auth()->user(), 25);

        return view('admin.ticket.show', compact('ticket', 'messages'));
    }

    /**
     * Add a public reply from support agent or administrator with optional attachments.
     */
    public function storeReply(TicketReplyRequest $request, Ticket $ticket): RedirectResponse
    {
        $attachments = $request->file('attachments', []);
        $this->messageService->addReply($ticket, auth()->user(), $request->validated('content'), $attachments);

        return redirect()->route('tickets.show', $ticket->id)
            ->with('success', 'Public reply sent successfully.');
    }

    /**
     * Add an internal note to the ticket with optional attachments.
     * Strictly restricted to internal staff users.
     */
    public function storeInternalNote(TicketInternalNoteRequest $request, Ticket $ticket): RedirectResponse
    {
        $attachments = $request->file('attachments', []);
        $this->messageService->addInternalNote($ticket, auth()->user(), $request->validated('content'), $attachments);

        return redirect()->route('tickets.show', $ticket->id)
            ->with('success', 'Internal note added successfully.');
    }

    /**
     * Securely download an attachment associated with a ticket message.
     */
    public function downloadAttachment(Ticket $ticket, TicketMessage $message, Media $media): StreamedResponse
    {
        return $this->messageService->downloadAttachment($ticket, $message, $media, auth()->user());
    }
}
