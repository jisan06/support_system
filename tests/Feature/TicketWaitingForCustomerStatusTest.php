<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\TicketImportService;
use App\Services\TicketService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class TicketWaitingForCustomerStatusTest extends TestCase
{
    use DatabaseTransactions, CreatesAdminUser;

    protected User $customer;
    protected User $adminUser;
    protected TicketService $ticketService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ticketService = app(TicketService::class);

        $this->customer = User::create([
            'name'              => 'Waiting Status Customer',
            'email'             => 'cust_waiting_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_CUSTOMER,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);

        $this->adminUser = $this->createAdminUser([
            'view_ticket',
            'create_ticket',
            'edit_ticket',
            'reply_ticket',
        ]);
    }

    /**
     * Test 1: Ticket can be created with waiting_for_customer status.
     */
    public function test_can_create_ticket_with_waiting_for_customer_status(): void
    {
        $ticket = $this->ticketService->createTicket([
            'customer_id' => $this->customer->id,
            'subject'     => 'Verification of Waiting Status Creation',
            'description' => 'Initial inquiry text for waiting for customer ticket.',
            'priority'    => 'medium',
            'status'      => 'waiting_for_customer',
        ], $this->adminUser);

        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertEquals('waiting_for_customer', $ticket->status);
        $this->assertDatabaseHas('tickets', [
            'id'     => $ticket->id,
            'status' => 'waiting_for_customer',
        ]);
    }

    /**
     * Test 2: Admin can update ticket status to waiting_for_customer via PATCH endpoint.
     */
    public function test_admin_can_update_ticket_status_to_waiting_for_customer(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TCK-WFC-' . strtoupper(Str::random(5)),
            'customer_id'   => $this->customer->id,
            'subject'       => 'Needs Customer Clarification',
            'status'        => 'open',
            'priority'      => 'high',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->patch(route('tickets.update_status', $ticket->id), [
                'status' => 'waiting_for_customer',
            ]);

        $response->assertRedirect(route('tickets.show', $ticket->id));
        $response->assertSessionHas('success', 'Ticket status updated successfully.');

        $ticket->refresh();
        $this->assertEquals('waiting_for_customer', $ticket->status);
    }

    /**
     * Test 3: Status change to waiting_for_customer dispatches customer notification with formatted label.
     */
    public function test_status_change_to_waiting_for_customer_notifies_customer(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TCK-NOTIF-' . strtoupper(Str::random(5)),
            'customer_id'   => $this->customer->id,
            'subject'       => 'Clarification Required',
            'status'        => 'open',
            'priority'      => 'medium',
        ]);

        $this->ticketService->updateStatus($ticket, 'waiting_for_customer');

        $notification = $this->customer->notifications()->latest('id')->first();

        $this->assertNotNull($notification, 'Status change notification was not created.');
        $this->assertStringContainsString("Ticket #{$ticket->ticket_number} Status Updated", $notification->data['title']);
        $this->assertStringContainsString('Waiting for Customer', $notification->data['message']);
    }

    /**
     * Test 4: Admin ticket listing and details display waiting_for_customer correctly.
     */
    public function test_admin_ui_displays_waiting_for_customer(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TCK-ADM-UI-' . strtoupper(Str::random(5)),
            'customer_id'   => $this->customer->id,
            'subject'       => 'Admin UI Waiting Display Test',
            'status'        => 'waiting_for_customer',
            'priority'      => 'medium',
        ]);

        // Listing view
        $indexResponse = $this->actingAs($this->adminUser)->get(route('tickets.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('waiting for customer', false);
        $indexResponse->assertSee($ticket->ticket_number);

        // Details view
        $showResponse = $this->actingAs($this->adminUser)->get(route('tickets.show', $ticket->id));
        $showResponse->assertOk();
        $showResponse->assertSee('Waiting for Customer');
        $showResponse->assertSee('value="waiting_for_customer" selected', false);
    }

    /**
     * Test 5: Customer ticket listing and details display waiting_for_customer correctly.
     */
    public function test_customer_ui_displays_waiting_for_customer(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TCK-CUST-UI-' . strtoupper(Str::random(5)),
            'customer_id'   => $this->customer->id,
            'subject'       => 'Customer UI Waiting Display Test',
            'status'        => 'waiting_for_customer',
            'priority'      => 'medium',
        ]);

        // Customer listing view
        $indexResponse = $this->actingAs($this->customer)->get(route('customer.tickets.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Waiting for You');
        $indexResponse->assertSee('status-waiting_for_customer');
        $indexResponse->assertSee($ticket->ticket_number);

        // Customer details view
        $showResponse = $this->actingAs($this->customer)->get(route('customer.tickets.show', $ticket));
        $showResponse->assertOk();
        $showResponse->assertSee('pill-waiting_for_customer');
        $showResponse->assertSee('waiting for customer', false);
    }

    /**
     * Test 6: Search and filtering by waiting_for_customer status works for both Admin and Customer.
     */
    public function test_search_and_filtering_by_waiting_for_customer(): void
    {
        $waitingTicket = Ticket::create([
            'ticket_number' => 'TCK-FILTER-W1',
            'customer_id'   => $this->customer->id,
            'subject'       => 'Filter Target Waiting Ticket',
            'status'        => 'waiting_for_customer',
            'priority'      => 'medium',
        ]);

        $openTicket = Ticket::create([
            'ticket_number' => 'TCK-FILTER-O2',
            'customer_id'   => $this->customer->id,
            'subject'       => 'Filter Target Open Ticket',
            'status'        => 'open',
            'priority'      => 'medium',
        ]);

        // Admin Filter
        $adminFilter = $this->actingAs($this->adminUser)->get(route('tickets.index', ['status' => 'waiting_for_customer']));
        $adminFilter->assertOk();
        $adminFilter->assertSee('TCK-FILTER-W1');
        $adminFilter->assertDontSee('TCK-FILTER-O2');

        // Customer Filter
        $customerFilter = $this->actingAs($this->customer)->get(route('customer.tickets.index', ['status' => 'waiting_for_customer']));
        $customerFilter->assertOk();
        $customerFilter->assertSee('TCK-FILTER-W1');
        $customerFilter->assertDontSee('TCK-FILTER-O2');
    }

    /**
     * Test 7: Dashboard statistics and breakdown include waiting_for_customer.
     */
    public function test_dashboard_statistics_include_waiting_for_customer(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TCK-DASH-W1',
            'customer_id'   => $this->customer->id,
            'subject'       => 'Dashboard Counting Test Ticket',
            'status'        => 'waiting_for_customer',
            'priority'      => 'urgent',
        ]);

        $dashboardService = app(DashboardService::class);
        $stats = $dashboardService->getTicketStats();
        $insights = $dashboardService->getTicketInsights();

        $this->assertArrayHasKey('waiting_for_customer', $stats);
        $this->assertGreaterThanOrEqual(1, $stats['waiting_for_customer']);

        $this->assertArrayHasKey('waiting_for_customer', $insights['volume_by_status']);
        $this->assertEquals('Waiting for Customer', $insights['volume_by_status']['waiting_for_customer']['label']);
        $this->assertGreaterThanOrEqual(1, $insights['volume_by_status']['waiting_for_customer']['count']);
    }

    /**
     * Test 8: SLA behavior when ticket enters or leaves waiting_for_customer.
     */
    public function test_sla_behavior_with_waiting_for_customer(): void
    {
        $ticket = Ticket::create([
            'ticket_number'         => 'TCK-SLA-W1',
            'customer_id'           => $this->customer->id,
            'subject'               => 'SLA Behavior Verification',
            'status'                => 'resolved',
            'priority'              => 'high',
            'first_response_due_at' => now()->addHours(2),
            'resolution_due_at'     => now()->addHours(8),
            'resolved_at'           => now()->subHour(),
        ]);

        // Reopening from resolved to waiting_for_customer must clear resolved_at
        $this->ticketService->updateStatus($ticket, 'waiting_for_customer');
        $ticket->refresh();

        $this->assertEquals('waiting_for_customer', $ticket->status);
        $this->assertNull($ticket->resolved_at, 'Reopening to waiting_for_customer must clear resolved_at.');
        $this->assertNotNull($ticket->first_response_due_at);
        $this->assertNotNull($ticket->resolution_due_at);

        // Transition from open to waiting_for_customer does not falsely mark resolution
        $openTicket = Ticket::create([
            'ticket_number'         => 'TCK-SLA-W2',
            'customer_id'           => $this->customer->id,
            'subject'               => 'SLA Open to Waiting Transition',
            'status'                => 'open',
            'priority'              => 'low',
            'first_response_due_at' => now()->addHours(4),
            'resolution_due_at'     => now()->addHours(24),
            'resolved_at'           => null,
        ]);

        $this->ticketService->updateStatus($openTicket, 'waiting_for_customer');
        $openTicket->refresh();

        $this->assertEquals('waiting_for_customer', $openTicket->status);
        $this->assertNull($openTicket->resolved_at);
    }

    /**
     * Test 9: CSV import successfully imports tickets with waiting_for_customer status.
     */
    public function test_csv_import_accepts_waiting_for_customer_status(): void
    {
        Storage::fake('local');

        $rows = [
            ['customer_email', 'subject', 'description', 'priority', 'category', 'status', 'assigned_agent'],
            [$this->customer->email, 'Imported Waiting Ticket', 'Inquiry content.', 'medium', 'Technical Support', 'waiting_for_customer', ''],
        ];

        $filename = 'waiting_import_test_' . Str::random(6) . '.csv';
        $handle = fopen('php://memory', 'r+');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        $file = UploadedFile::fake()->createWithContent($filename, $content);

        $importService = app(TicketImportService::class);
        $import = $importService->initiateImport($file, $this->adminUser);
        $importService->processImport($import);

        $import->refresh();
        $this->assertEquals('completed', $import->status);
        $this->assertEquals(1, $import->successful_rows);
        $this->assertEquals(0, $import->failed_rows);

        $importedTicket = Ticket::where('subject', 'Imported Waiting Ticket')->first();
        $this->assertNotNull($importedTicket);
        $this->assertEquals('waiting_for_customer', $importedTicket->status);
    }
}
