<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerStoreTicketRequest;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TicketController extends Controller
{
    protected TicketService $ticketService;

    public function __construct(TicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }

    /**
     * Show the ticket creation form for the authenticated customer.
     */
    public function create(): View
    {
        $departments = [
            'Technical Support',
            'Billing & Payments',
            'Account & Access',
            'General Inquiry',
        ];

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
}

