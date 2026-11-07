<?php

namespace Tests\Feature;

use App\Enums\SlaStatus;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class AdminTicketSearchAndCategoryTest extends TestCase
{
    use DatabaseTransactions, CreatesAdminUser;

    protected User $admin;
    protected User $agent1;
    protected User $agent2;
    protected User $customer1;
    protected User $customer2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createAdminUser(['view_ticket', 'create_ticket', 'edit_ticket']);

        $this->agent1 = User::create([
            'name'              => 'Agent Alpha',
            'email'             => 'agent_alpha_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_ADMIN,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);

        $this->agent2 = User::create([
            'name'              => 'Agent Beta',
            'email'             => 'agent_beta_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_ADMIN,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);

        $this->customer1 = User::create([
            'name'              => 'Alice Wonderland',
            'email'             => 'alice_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_CUSTOMER,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);

        $this->customer2 = User::create([
            'name'              => 'Bob Builder',
            'email'             => 'bob_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_CUSTOMER,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Helper to create ticket directly.
     */
    protected function makeTicket(array $attributes = []): Ticket
    {
        return Ticket::create(array_merge([
            'ticket_number' => 'TCK-' . strtoupper(Str::random(8)),
            'customer_id'   => $this->customer1->id,
            'assigned_to'   => $this->agent1->id,
            'subject'       => 'Test Ticket Subject ' . Str::random(5),
            'status'        => 'open',
            'priority'      => 'medium',
            'department'    => 'Technical Support',
            'created_at'    => now(),
            'last_reply_at' => now(),
        ], $attributes));
    }

    /**
     * Test 1: Admin and Customer forms display the exact same configured category options.
     */
    public function test_admin_and_customer_forms_display_same_category_options(): void
    {
        $categories = config('ticket.categories');
        $this->assertNotEmpty($categories);

        // Check Admin ticket create form
        $adminRes = $this->actingAs($this->admin)->get(route('tickets.create'));
        $adminRes->assertOk();
        foreach ($categories as $cat) {
            $adminRes->assertSee($cat);
        }

        // Check Customer ticket create form
        $custRes = $this->actingAs($this->customer1)->get(route('customer.tickets.create'));
        $custRes->assertOk();
        foreach ($categories as $cat) {
            $custRes->assertSee($cat);
        }
    }

    /**
     * Test 2: Both Admin and Customer forms successfully create tickets with valid categories.
     */
    public function test_both_forms_successfully_create_tickets_with_valid_categories(): void
    {
        // Admin creates ticket with Technical Support
        $adminRes = $this->actingAs($this->admin)->post(route('tickets.store'), [
            'customer_id' => $this->customer1->id,
            'assigned_to' => $this->agent1->id,
            'subject'     => 'Server down critical error',
            'department'  => 'Technical Support',
            'priority'    => 'urgent',
            'description' => 'The main database server is unreachable.',
        ]);
        $adminRes->assertRedirect();
        $adminTicket = Ticket::where('subject', 'Server down critical error')->first();
        $this->assertNotNull($adminTicket);
        $this->assertEquals('Technical Support', $adminTicket->department);

        // Customer creates ticket with Billing & Payments
        $custRes = $this->actingAs($this->customer1)->post(route('customer.tickets.store'), [
            'subject'     => 'Double charged for subscription',
            'department'  => 'Billing & Payments',
            'priority'    => 'high',
            'description' => 'I was charged twice on my credit card this morning.',
        ]);
        $custRes->assertRedirect(route('customer.dashboard'));
        $custTicket = Ticket::where('subject', 'Double charged for subscription')->first();
        $this->assertNotNull($custTicket);
        $this->assertEquals('Billing & Payments', $custTicket->department);
    }

    /**
     * Test 3: Both Admin and Customer forms reject invalid categories.
     */
    public function test_both_forms_reject_invalid_categories(): void
    {
        // Admin form rejects invalid category
        $adminRes = $this->actingAs($this->admin)->post(route('tickets.store'), [
            'customer_id' => $this->customer1->id,
            'assigned_to' => $this->agent1->id,
            'subject'     => 'Test invalid category',
            'department'  => 'NonExistentCategory123',
            'priority'    => 'low',
            'description' => 'Some description text here.',
        ]);
        $adminRes->assertSessionHasErrors('department');

        // Customer form rejects invalid category
        $custRes = $this->actingAs($this->customer1)->post(route('customer.tickets.store'), [
            'subject'     => 'Test invalid customer category',
            'department'  => 'NonExistentCategory123',
            'priority'    => 'low',
            'description' => 'Some description text here.',
        ]);
        $custRes->assertSessionHasErrors('department');
    }

    /**
     * Test 4: Existing ticket category values remain intact and are included in filter options.
     */
    public function test_existing_ticket_category_values_remain_intact(): void
    {
        $legacyTicket = $this->makeTicket([
            'department' => 'Legacy Hardware Department',
            'subject'    => 'Legacy Ticket Subject',
        ]);

        $this->assertEquals('Legacy Hardware Department', $legacyTicket->fresh()->department);

        // Admin ticket index loads existing categories dynamically
        $response = $this->actingAs($this->admin)->get(route('tickets.index'));
        $response->assertOk();
        $response->assertSee('Legacy Hardware Department');
    }

    /**
     * Test 5: Admin search by ticket number, subject, customer name and email.
     */
    public function test_admin_search_by_ticket_number_subject_customer_name_and_email(): void
    {
        $ticket1 = $this->makeTicket([
            'ticket_number' => 'TCK-UNIQUE99',
            'subject'       => 'Quantum Computation Server Error',
            'customer_id'   => $this->customer1->id, // Alice Wonderland, alice_...
        ]);

        $ticket2 = $this->makeTicket([
            'ticket_number' => 'TCK-OTHER11',
            'subject'       => 'Routine Printer Maintenance',
            'customer_id'   => $this->customer2->id, // Bob Builder, bob_...
        ]);

        // Search by Ticket Number
        $resByNumber = $this->actingAs($this->admin)->get(route('tickets.index', ['search' => 'UNIQUE99']));
        $resByNumber->assertOk();
        $resByNumber->assertSee('Quantum Computation Server Error');
        $resByNumber->assertDontSee('Routine Printer Maintenance');

        // Search by Subject keyword
        $resBySubject = $this->actingAs($this->admin)->get(route('tickets.index', ['search' => 'Computation Server']));
        $resBySubject->assertOk();
        $resBySubject->assertSee('TCK-UNIQUE99');
        $resBySubject->assertDontSee('TCK-OTHER11');

        // Search by Customer Name
        $resByName = $this->actingAs($this->admin)->get(route('tickets.index', ['search' => 'Alice Wonderland']));
        $resByName->assertOk();
        $resByName->assertSee('TCK-UNIQUE99');
        $resByName->assertDontSee('TCK-OTHER11');

        // Search by Customer Email
        $resByEmail = $this->actingAs($this->admin)->get(route('tickets.index', ['search' => $this->customer2->email]));
        $resByEmail->assertOk();
        $resByEmail->assertSee('TCK-OTHER11');
        $resByEmail->assertDontSee('TCK-UNIQUE99');
    }

    /**
     * Test 6: Individual filters (Status, Priority, Category, Assigned Agent, Customer, SLA).
     */
    public function test_admin_individual_filters(): void
    {
        $openTicket = $this->makeTicket([
            'subject'     => 'Open Bug Report',
            'status'      => 'open',
            'priority'    => 'urgent',
            'department'  => 'Technical Support',
            'assigned_to' => $this->agent1->id,
            'customer_id' => $this->customer1->id,
        ]);

        $closedTicket = $this->makeTicket([
            'subject'     => 'Closed Inquiry',
            'status'      => 'closed',
            'priority'    => 'low',
            'department'  => 'General Inquiry',
            'assigned_to' => null,
            'customer_id' => $this->customer2->id,
        ]);

        // Status filter
        $resStatus = $this->actingAs($this->admin)->get(route('tickets.index', ['status' => 'closed']));
        $resStatus->assertSee('Closed Inquiry');
        $resStatus->assertDontSee('Open Bug Report');

        // Priority filter
        $resPriority = $this->actingAs($this->admin)->get(route('tickets.index', ['priority' => 'urgent']));
        $resPriority->assertSee('Open Bug Report');
        $resPriority->assertDontSee('Closed Inquiry');

        // Category filter
        $resCategory = $this->actingAs($this->admin)->get(route('tickets.index', ['category' => 'Technical Support']));
        $resCategory->assertSee('Open Bug Report');
        $resCategory->assertDontSee('Closed Inquiry');

        // Assigned Agent (unassigned)
        $resUnassigned = $this->actingAs($this->admin)->get(route('tickets.index', ['assigned_to' => 'unassigned']));
        $resUnassigned->assertSee('Closed Inquiry');
        $resUnassigned->assertDontSee('Open Bug Report');

        // Assigned Agent (specific agent)
        $resAgent = $this->actingAs($this->admin)->get(route('tickets.index', ['assigned_to' => $this->agent1->id]));
        $resAgent->assertSee('Open Bug Report');
        $resAgent->assertDontSee('Closed Inquiry');

        // Customer filter
        $resCustomer = $this->actingAs($this->admin)->get(route('tickets.index', ['customer_id' => $this->customer2->id]));
        $resCustomer->assertSee('Closed Inquiry');
        $resCustomer->assertDontSee('Open Bug Report');
    }

    /**
     * Test 7: SLA Status filter (breached, completed, on_track).
     */
    public function test_admin_sla_status_filtering(): void
    {
        // Breached ticket: first response was due in past and not responded
        $breachedTicket = $this->makeTicket([
            'subject'               => 'SLA Breached Ticket',
            'status'                => 'open',
            'first_response_due_at' => now()->subHours(5),
            'first_responded_at'    => null,
            'resolution_due_at'     => now()->addHours(20),
        ]);

        // Completed ticket: resolved within SLA
        $completedTicket = $this->makeTicket([
            'subject'               => 'SLA Completed Ticket',
            'status'                => 'resolved',
            'first_response_due_at' => now()->addHours(2),
            'first_responded_at'    => now()->subHour(),
            'resolution_due_at'     => now()->addHours(8),
            'resolved_at'           => now()->subMinutes(30),
        ]);

        // Filter by Breached
        $resBreached = $this->actingAs($this->admin)->get(route('tickets.index', ['sla_status' => 'breached']));
        $resBreached->assertOk();
        $resBreached->assertSee('SLA Breached Ticket');
        $resBreached->assertDontSee('SLA Completed Ticket');

        // Filter by Completed
        $resCompleted = $this->actingAs($this->admin)->get(route('tickets.index', ['sla_status' => 'completed']));
        $resCompleted->assertOk();
        $resCompleted->assertSee('SLA Completed Ticket');
        $resCompleted->assertDontSee('SLA Breached Ticket');
    }

    /**
     * Test 8: Combined filters work together seamlessly.
     */
    public function test_admin_combined_filters_work_together(): void
    {
        $match = $this->makeTicket([
            'subject'     => 'Target Combined Ticket',
            'status'      => 'open',
            'priority'    => 'high',
            'department'  => 'Account & Access',
            'assigned_to' => $this->agent1->id,
            'customer_id' => $this->customer1->id,
        ]);

        $differentCategory = $this->makeTicket([
            'subject'     => 'Mismatch Category Ticket',
            'status'      => 'open',
            'priority'    => 'high',
            'department'  => 'Technical Support',
            'assigned_to' => $this->agent1->id,
            'customer_id' => $this->customer1->id,
        ]);

        $differentPriority = $this->makeTicket([
            'subject'     => 'Mismatch Priority Ticket',
            'status'      => 'open',
            'priority'    => 'low',
            'department'  => 'Account & Access',
            'assigned_to' => $this->agent1->id,
            'customer_id' => $this->customer1->id,
        ]);

        $res = $this->actingAs($this->admin)->get(route('tickets.index', [
            'status'      => 'open',
            'priority'    => 'high',
            'category'    => 'Account & Access',
            'assigned_to' => $this->agent1->id,
            'customer_id' => $this->customer1->id,
        ]));

        $res->assertOk();
        $res->assertSee('Target Combined Ticket');
        $res->assertDontSee('Mismatch Category Ticket');
        $res->assertDontSee('Mismatch Priority Ticket');
    }

    /**
     * Test 9: Date range filter and impossible date range safety.
     */
    public function test_admin_date_range_filtering(): void
    {
        $recent = $this->makeTicket([
            'subject'    => 'Recent Yesterday Ticket',
            'created_at' => now()->subDay(),
        ]);

        $old = $this->makeTicket([
            'subject'    => 'Old Months Ago Ticket',
            'created_at' => now()->subDays(60),
        ]);

        // Filter last 7 days
        $res = $this->actingAs($this->admin)->get(route('tickets.index', [
            'date_from' => now()->subDays(7)->format('Y-m-d'),
            'date_to'   => now()->format('Y-m-d'),
        ]));

        $res->assertOk();
        $res->assertSee('Recent Yesterday Ticket');
        $res->assertDontSee('Old Months Ago Ticket');

        // Impossible date range (date_from > date_to) returns zero safely
        $impossibleRes = $this->actingAs($this->admin)->get(route('tickets.index', [
            'date_from' => now()->format('Y-m-d'),
            'date_to'   => now()->subDays(5)->format('Y-m-d'),
        ]));
        $impossibleRes->assertOk();
        $impossibleRes->assertDontSee('Recent Yesterday Ticket');
        $impossibleRes->assertDontSee('Old Months Ago Ticket');
    }

    /**
     * Test 10: Pagination preserves active filter query parameters.
     */
    public function test_admin_pagination_preserves_query_parameters(): void
    {
        // Create 18 tickets with priority high
        for ($i = 1; $i <= 18; $i++) {
            $this->makeTicket([
                'subject'  => "Paginated High Ticket {$i}",
                'priority' => 'high',
                'status'   => 'open',
            ]);
        }

        $res = $this->actingAs($this->admin)->get(route('tickets.index', [
            'priority' => 'high',
            'status'   => 'open',
        ]));

        $res->assertOk();
        // Check pagination links include query parameters
        $res->assertSee('priority=high');
        $res->assertSee('status=open');
    }

    /**
     * Test 11: Permission and authorization checks.
     */
    public function test_admin_ticket_listing_permission_check(): void
    {
        // Admin with view_ticket permission can view
        $authorizedAdmin = $this->createAdminUser(['view_ticket']);
        $authRes = $this->actingAs($authorizedAdmin)->get(route('tickets.index'));
        $authRes->assertOk();

        // Admin without view_ticket permission is denied (403)
        $unauthorizedAdmin = $this->createAdminUser(['view_media']);
        $deniedRes = $this->actingAs($unauthorizedAdmin)->get(route('tickets.index'));
        $deniedRes->assertForbidden();

        // Customer cannot access admin ticket index
        $custRes = $this->actingAs($this->customer1)->get(route('tickets.index'));
        $custRes->assertForbidden();
    }
}

