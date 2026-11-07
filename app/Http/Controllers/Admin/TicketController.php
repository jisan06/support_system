<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\TicketInternalNoteRequest;
use App\Http\Requests\TicketReplyRequest;
use App\Models\Media;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\TicketMessageService;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
     * Show form to manually create a new ticket.
     */
    public function create(): View
    {
        $customers = User::where('account_type', User::TYPE_CUSTOMER)->where('status', 'Active')->orderBy('name')->get(['id', 'name', 'email']);
        $agents = User::where('account_type', User::TYPE_ADMIN)->where('status', 'Active')->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.ticket.create', compact('customers', 'agents'));
    }

    /**
     * Store a newly created ticket with initial message and optional attachments.
     */
    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $ticket = $this->ticketService->createTicket(
            $request->validated(),
            auth()->user(),
            $request->file('attachments', []),
            $request->input('media_ids', [])
        );

        return redirect()->route('tickets.show', $ticket->id)
            ->with('success', 'Ticket created successfully.');
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
        $mediaIds = $request->input('media_ids', []);
        $this->messageService->addReply($ticket, auth()->user(), $request->validated('content'), $attachments, $mediaIds);

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
        $mediaIds = $request->input('media_ids', []);
        $this->messageService->addInternalNote($ticket, auth()->user(), $request->validated('content'), $attachments, $mediaIds);

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

    /**
     * Update ticket status and trigger SLA resolution tracking.
     */
    public function updateStatus(Request $request, Ticket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
        ]);

        $this->ticketService->updateStatus($ticket, $validated['status']);

        return redirect()->route('tickets.show', $ticket->id)
            ->with('success', 'Ticket status updated successfully.');
    }

    /**
     * Update ticket priority and recalculate pending SLA deadlines.
     */
    public function updatePriority(Request $request, Ticket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        $this->ticketService->updatePriority($ticket, $validated['priority']);

        return redirect()->route('tickets.show', $ticket->id)
            ->with('success', 'Ticket priority updated and SLA recalculated.');
    }
}
