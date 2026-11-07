<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerStoreTicketRequest;
use App\Http\Requests\TicketReplyRequest;
use App\Models\Media;
use App\Models\Ticket;
use App\Models\TicketMessage;
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
     * Display a listing of tickets belonging to the authenticated customer.
     * Supports pagination, keyword search, date range, and filtering by status, priority, and department.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->query(), [
            'status'     => ['nullable', 'string', \Illuminate\Validation\Rule::in(['all', '', 'open', 'in_progress', 'resolved', 'closed'])],
            'priority'   => ['nullable', 'string', \Illuminate\Validation\Rule::in(['all', '', 'low', 'medium', 'high', 'urgent'])],
            'department' => ['nullable', 'string', 'max:100'],
            'date_from'  => ['nullable', 'date_format:Y-m-d'],
            'date_to'    => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'search'     => ['nullable', 'string', 'max:100'],
        ], [
            'date_to.after_or_equal' => 'The end date must be a date after or equal to the start date.',
            'date_from.date_format'  => 'The start date format must be YYYY-MM-DD.',
            'date_to.date_format'    => 'The end date format must be YYYY-MM-DD.',
            'status.in'              => 'The selected ticket status is invalid.',
            'priority.in'            => 'The selected ticket priority is invalid.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('customer.tickets.index')
                ->withErrors($validator)
                ->withInput();
        }

        $customer = $request->user();
        $filters = [
            'status'     => $request->query('status'),
            'priority'   => $request->query('priority'),
            'department' => $request->query('department'),
            'date_from'  => $request->query('date_from'),
            'date_to'    => $request->query('date_to'),
            'search'     => $request->query('search'),
        ];

        $tickets = $this->ticketService->getTicketsForUser($customer, $filters, 10);
        $tickets->appends($request->query());

        // Status counts for customer filter pills/tabs
        $statusCounts = [
            'all'         => Ticket::where('customer_id', $customer->id)->count(),
            'open'        => Ticket::where('customer_id', $customer->id)->where('status', 'open')->count(),
            'in_progress' => Ticket::where('customer_id', $customer->id)->where('status', 'in_progress')->count(),
            'resolved'    => Ticket::where('customer_id', $customer->id)->where('status', 'resolved')->count(),
            'closed'      => Ticket::where('customer_id', $customer->id)->where('status', 'closed')->count(),
        ];

        $defaultDepartments = [
            'Technical Support',
            'Billing & Payments',
            'Account & Access',
            'General Inquiry',
        ];
        $customDepartments = Ticket::where('customer_id', $customer->id)
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->pluck('department')
            ->toArray();
        $departments = array_values(array_unique(array_merge($defaultDepartments, $customDepartments)));
        sort($departments);

        $priorities = [
            'low'    => 'Low',
            'medium' => 'Medium',
            'high'   => 'High',
            'urgent' => 'Urgent',
        ];

        return view('customer.tickets.index', compact('tickets', 'filters', 'statusCounts', 'departments', 'priorities'));
    }

    /**
     * Show the ticket creation form for the authenticated customer.
     */
    public function create(): View
    {
        $departments = $this->ticketService->getAvailableCategories();

        $priorities = [
            'low'    => 'Low',
            'medium' => 'Medium',
            'high'   => 'High',
            'urgent' => 'Urgent',
        ];

        return view('customer.tickets.create', compact('departments', 'priorities'));
    }

    /**
     * Store a newly created ticket by the authenticated customer.
     */
    public function store(CustomerStoreTicketRequest $request): RedirectResponse
    {
        $customer = $request->user();

        $data = $request->validated();
        // Never accept customer_id from request; always derive from authenticated user
        $data['customer_id'] = $customer->id;
        // Customers cannot assign tickets to agents or set internal-only fields
        unset($data['assigned_to']);

        $ticket = $this->ticketService->createTicket(
            $data,
            $customer,
            $request->file('attachments', []),
            $request->input('media_ids', [])
        );

        return redirect()->route('customer.dashboard')
            ->with('success', 'Your support ticket has been created successfully (Ticket #' . $ticket->ticket_number . ').');
    }

    /**
     * Display the specified ticket details and its public conversation history.
     * Enforces strict customer ownership; customers can only access their own tickets.
     */
    public function show(Request $request, Ticket $ticket): View
    {
        $customer = $request->user();

        // Enforce ticket ownership at the backend
        if ((int) $ticket->customer_id !== (int) $customer->id) {
            abort(403, 'You are not authorized to access this support ticket.');
        }

        $ticket->load(['customer', 'customer.media', 'assignedTo', 'assignedTo.media']);

        // Only public messages are retrieved for customers; internal notes are never exposed
        $messages = $this->messageService->getConversation($ticket, $customer, 25);

        return view('customer.tickets.show', compact('ticket', 'messages'));
    }

    /**
     * Add a customer reply to the ticket conversation.
     * Enforces strict ticket ownership.
     */
    public function reply(TicketReplyRequest $request, Ticket $ticket): RedirectResponse
    {
        $customer = $request->user();

        if ((int) $ticket->customer_id !== (int) $customer->id) {
            abort(403, 'You are not authorized to update this support ticket.');
        }

        $attachments = $request->file('attachments', []);
        $mediaIds = $request->input('media_ids', []);

        $this->messageService->addReply(
            $ticket,
            $customer,
            $request->validated('content'),
            $attachments,
            $mediaIds
        );

        return redirect()->route('customer.tickets.show', $ticket->id)
            ->with('success', 'Your reply has been posted successfully.');
    }

    /**
     * Securely download or view an attachment for the authenticated customer.
     */
    public function downloadAttachment(
        Request $request,
        Ticket $ticket,
        TicketMessage $message,
        Media $media
    ): StreamedResponse {
        return $this->messageService->downloadAttachment($ticket, $message, $media, $request->user());
    }
}
