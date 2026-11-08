<?php

namespace Tests\Feature;

use App\Jobs\ProcessTicketImportJob;
use App\Models\Ticket;
use App\Models\TicketImport;
use App\Models\User;
use App\Services\TicketImportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class TicketCsvImportTest extends TestCase
{
    use DatabaseTransactions, CreatesAdminUser;

    protected User $admin;
    protected User $agent;
    protected User $customer1;
    protected User $customer2;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->admin = $this->createAdminUser(['create_ticket', 'view_ticket']);

        $this->agent = User::create([
            'name'              => 'Assigned Staff',
            'email'             => 'staff_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_ADMIN,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);

        $this->customer1 = User::create([
            'name'              => 'Customer One',
            'email'             => 'customer_one_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_CUSTOMER,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);

        $this->customer2 = User::create([
            'name'              => 'Customer Two',
            'email'             => 'customer_two_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_CUSTOMER,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Helper to create a fake uploaded CSV file from array of rows.
     */
    protected function createCsvFile(string $name, array $rows): UploadedFile
    {
        $content = '';
        foreach ($rows as $row) {
            $f = fopen('php://memory', 'r+');
            fputcsv($f, $row);
            rewind($f);
            $content .= stream_get_contents($f);
            fclose($f);
        }

        return UploadedFile::fake()->createWithContent($name, $content);
    }

    /**
     * Test 1: Authorized user can view import page, while unauthorized user is forbidden.
     */
    public function test_authorization_and_permission_checks(): void
    {
        // Authorized admin
        $res = $this->actingAs($this->admin)->get(route('tickets.import.index'));
        $res->assertOk();
        $res->assertSee('Upload Tickets CSV');
        $res->assertSee('Download Sample CSV');

        // Admin without create_ticket
        $unauthorizedAdmin = $this->createAdminUser(['view_ticket']);
        $deniedAdmin = $this->actingAs($unauthorizedAdmin)->get(route('tickets.import.index'));
        $deniedAdmin->assertForbidden();

        // Customer cannot access admin import
        $custRes = $this->actingAs($this->customer1)->get(route('tickets.import.index'));
        $custRes->assertForbidden();
    }

    /**
     * Test 2: Downloadable sample CSV template has valid structure.
     */
    public function test_sample_template_download(): void
    {
        $res = $this->actingAs($this->admin)->get(route('tickets.import.template'));
        $res->assertOk();
        $res->assertHeader('Content-Disposition', 'attachment; filename="ticket_import_template.csv"');
        $content = $res->streamedContent();
        $this->assertStringContainsString('customer_email,subject,description,priority,category,status,assigned_agent', $content);
    }

    /**
     * Test 3: Valid CSV upload queues job and processes tickets correctly.
     */
    public function test_valid_csv_import_processing(): void
    {
        Queue::fake([ProcessTicketImportJob::class]);

        $file = $this->createCsvFile('tickets_valid.csv', [
            ['customer_email', 'subject', 'description', 'priority', 'category', 'status', 'assigned_agent'],
            [$this->customer1->email, 'Imported Issue 1', 'First description here.', 'high', 'Technical Support', 'open', $this->agent->email],
            [$this->customer2->email, 'Imported Issue 2', 'Second description here.', 'medium', 'Billing & Payments', 'in_progress', ''],
        ]);

        $uploadRes = $this->actingAs($this->admin)->post(route('tickets.import.store'), [
            'csv_file' => $file,
        ]);

        $uploadRes->assertRedirect(route('tickets.import.index'));
        $uploadRes->assertSessionHas('success');

        $import = TicketImport::latest('id')->first();
        $this->assertNotNull($import);
        $this->assertEquals('tickets_valid.csv', $import->file_name);
        $this->assertEquals(2, $import->total_rows);
        $this->assertEquals('pending', $import->status);

        Queue::assertPushed(ProcessTicketImportJob::class, function ($job) use ($import) {
            return $job->importId === $import->id;
        });

        // Now manually process the job to test business logic execution
        $service = app(TicketImportService::class);
        $service->processImport($import);

        $import->refresh();
        $this->assertEquals('completed', $import->status);
        $this->assertEquals(2, $import->successful_rows);
        $this->assertEquals(0, $import->failed_rows);
        $this->assertEquals(0, $import->duplicate_rows);

        // Verify tickets in database
        $ticket1 = Ticket::where('subject', 'Imported Issue 1')->first();
        $this->assertNotNull($ticket1);
        $this->assertEquals($this->customer1->id, $ticket1->customer_id);
        $this->assertEquals($this->agent->id, $ticket1->assigned_to);
        $this->assertEquals('high', $ticket1->priority);
        $this->assertEquals('Technical Support', $ticket1->department);
        $this->assertNotNull($ticket1->first_response_due_at); // SLA deadline initialized

        $ticket2 = Ticket::where('subject', 'Imported Issue 2')->first();
        $this->assertNotNull($ticket2);
        $this->assertEquals($this->customer2->id, $ticket2->customer_id);
        $this->assertNull($ticket2->assigned_to);
        $this->assertEquals('in_progress', $ticket2->status);
    }

    /**
     * Test 4: CSV with missing required header columns is rejected.
     */
    public function test_csv_with_missing_required_headers_fails(): void
    {
        $file = $this->createCsvFile('missing_headers.csv', [
            ['customer_email', 'priority'], // Missing subject and description
            [$this->customer1->email, 'high'],
        ]);

        $import = app(TicketImportService::class)->initiateImport($file, $this->admin);

        app(TicketImportService::class)->processImport($import);

        $import->refresh();
        $this->assertEquals('failed', $import->status);
        $this->assertNotEmpty($import->errors);
        $this->assertEquals('Header Error', $import->errors[0]['type']);
        $this->assertStringContainsString('Missing required header column', $import->errors[0]['reason']);
    }

    /**
     * Test 5: Invalid individual rows are reported and do not block valid rows.
     */
    public function test_invalid_individual_rows_fail_independently(): void
    {
        $file = $this->createCsvFile('mixed_tickets.csv', [
            ['customer_email', 'subject', 'description', 'priority', 'category', 'status', 'assigned_agent'],
            // Valid row
            [$this->customer1->email, 'Valid Ticket 1', 'Valid description.', 'low', 'General Inquiry', 'open', ''],
            // Missing subject
            [$this->customer1->email, '', 'Missing subject text.', 'low', '', 'open', ''],
            // Short description
            [$this->customer1->email, 'Valid Subject', 'X', 'low', '', 'open', ''],
            // Nonexistent customer
            ['nonexistent_' . Str::random(5) . '@nowhere.test', 'Valid Subject', 'Valid description.', 'low', '', 'open', ''],
            // Invalid priority
            [$this->customer2->email, 'Invalid Priority Subject', 'Valid description.', 'super_critical', '', 'open', ''],
            // Invalid category
            [$this->customer2->email, 'Invalid Category Subject', 'Valid description.', 'medium', 'FakeCategory99', 'open', ''],
            // Invalid status
            [$this->customer2->email, 'Invalid Status Subject', 'Valid description.', 'medium', '', 'archived_invalid', ''],
            // Nonexistent assigned agent
            [$this->customer2->email, 'Invalid Agent Subject', 'Valid description.', 'medium', '', 'open', 'nonexistent_agent@test.com'],
        ]);

        $import = app(TicketImportService::class)->initiateImport($file, $this->admin);
        app(TicketImportService::class)->processImport($import);

        $import->refresh();
        $this->assertEquals('completed', $import->status);
        $this->assertEquals(1, $import->successful_rows);
        $this->assertEquals(7, $import->failed_rows);
        $this->assertEquals(0, $import->duplicate_rows);

        // Verify the only valid ticket was created
        $this->assertDatabaseHas('tickets', ['subject' => 'Valid Ticket 1']);
        $this->assertDatabaseMissing('tickets', ['subject' => 'Invalid Priority Subject']);
    }

    /**
     * Test 6: Duplicate detection handles tickets across ALL statuses (open, in_progress, resolved, closed).
     */
    public function test_duplicate_detection_handles_all_statuses(): void
    {
        // 1. Pre-existing resolved ticket in DB
        Ticket::create([
            'ticket_number' => 'TCK-RESOLVED',
            'customer_id'   => $this->customer1->id,
            'subject'       => 'Previously Resolved Issue',
            'status'        => 'resolved',
            'priority'      => 'high',
            'department'    => 'Technical Support',
            'last_reply_at' => now(),
            'resolved_at'   => now(),
        ]);

        // 2. Pre-existing closed ticket in DB
        Ticket::create([
            'ticket_number' => 'TCK-CLOSED',
            'customer_id'   => $this->customer2->id,
            'subject'       => 'Previously Closed Request',
            'status'        => 'closed',
            'priority'      => 'low',
            'department'    => 'Billing & Payments',
            'last_reply_at' => now(),
        ]);

        $file = $this->createCsvFile('duplicates_all_statuses.csv', [
            ['customer_email', 'subject', 'description', 'priority', 'category', 'status', 'assigned_agent'],
            // Matches resolved ticket in DB -> duplicate!
            [$this->customer1->email, 'Previously Resolved Issue', 'Description text.', 'medium', '', 'open', ''],
            // Matches closed ticket in DB -> duplicate!
            [$this->customer2->email, 'Previously Closed Request', 'Description text.', 'medium', '', 'open', ''],
            // Fresh valid ticket
            [$this->customer1->email, 'Completely Fresh Issue', 'Description text.', 'medium', '', 'open', ''],
            // In-batch duplicate of the row right above -> duplicate!
            [$this->customer1->email, 'Completely Fresh Issue', 'Another copy of same.', 'medium', '', 'open', ''],
        ]);

        $import = app(TicketImportService::class)->initiateImport($file, $this->admin);
        app(TicketImportService::class)->processImport($import);

        $import->refresh();
        $this->assertEquals('completed', $import->status);
        $this->assertEquals(1, $import->successful_rows);
        $this->assertEquals(0, $import->failed_rows);
        $this->assertEquals(3, $import->duplicate_rows);

        // Verify only 1 ticket exists for "Completely Fresh Issue"
        $this->assertEquals(1, Ticket::where('subject', 'Completely Fresh Issue')->count());
    }

    /**
     * Test 7: Status compatibility - unsupported statuses like waiting_for_customer are rejected.
     */
    public function test_unsupported_status_like_waiting_for_customer_is_rejected(): void
    {
        $file = $this->createCsvFile('unsupported_status.csv', [
            ['customer_email', 'subject', 'description', 'priority', 'category', 'status', 'assigned_agent'],
            [$this->customer1->email, 'Waiting Status Ticket', 'Description text.', 'low', '', 'waiting_for_customer', ''],
        ]);

        $import = app(TicketImportService::class)->initiateImport($file, $this->admin);
        app(TicketImportService::class)->processImport($import);

        $import->refresh();
        $this->assertEquals('completed', $import->status);
        $this->assertEquals(0, $import->successful_rows);
        $this->assertEquals(1, $import->failed_rows);

        $errorCsv = Storage::disk('local')->get("imports/tickets/errors_{$import->id}.csv");
        $this->assertStringContainsString('Invalid status [waiting_for_customer]', $errorCsv);
        $this->assertStringContainsString('Allowed: open, in_progress, resolved, closed', $errorCsv);
    }

    /**
     * Test 8: Large import error storage writes to disk file and caps preview array.
     */
    public function test_large_import_error_storage_writes_to_disk_and_caps_json_preview(): void
    {
        $rows = [
            ['customer_email', 'subject', 'description', 'priority', 'category', 'status', 'assigned_agent'],
        ];

        // Generate 60 invalid rows
        for ($i = 1; $i <= 60; $i++) {
            $rows[] = ['invalid_email_' . $i, "Subject {$i}", 'Description', 'medium', '', 'open', ''];
        }

        $file = $this->createCsvFile('many_errors.csv', $rows);
        $import = app(TicketImportService::class)->initiateImport($file, $this->admin);
        app(TicketImportService::class)->processImport($import);

        $import->refresh();
        $this->assertEquals(60, $import->failed_rows);

        // Database JSON preview should be capped at 50 to prevent database bloat
        $this->assertLessThanOrEqual(50, count($import->errors));

        // The disk file must contain all 60 error rows
        $this->assertTrue(Storage::disk('local')->exists("imports/tickets/errors_{$import->id}.csv"));
        $content = Storage::disk('local')->get("imports/tickets/errors_{$import->id}.csv");
        $this->assertStringContainsString('invalid_email_1', $content);
        $this->assertStringContainsString('invalid_email_60', $content);
    }

    /**
     * Test 9: Retry idempotency - simulated job crash does not duplicate tickets on retry.
     */
    public function test_retry_cannot_create_duplicate_tickets_after_crash(): void
    {
        $file = $this->createCsvFile('crash_recovery.csv', [
            ['customer_email', 'subject', 'description', 'priority', 'category', 'status', 'assigned_agent'],
            [$this->customer1->email, 'Crash Recovery Ticket', 'First description.', 'high', 'Technical Support', 'closed', ''],
            [$this->customer2->email, 'Second Ticket In File', 'Second description.', 'low', '', 'open', ''],
        ]);

        $import = app(TicketImportService::class)->initiateImport($file, $this->admin);

        // Simulate Run 1: Ticket 1 was created, but process crashed before updating processed_rows
        $service = app(TicketImportService::class);
        $service->processImport($import);

        $initialTicketCount = Ticket::where('subject', 'Crash Recovery Ticket')->count();
        $this->assertEquals(1, $initialTicketCount);

        // Reset import status to pending with processed_rows = 0 (simulating restart after crash)
        $import->update([
            'status'          => 'pending',
            'processed_rows'  => 0,
            'successful_rows' => 0,
            'duplicate_rows'  => 0,
        ]);

        // Run 2: Retry execution
        $service->processImport($import);

        // Verification: Ticket 1 must NOT have been duplicated despite status 'closed'!
        $finalTicketCount = Ticket::where('subject', 'Crash Recovery Ticket')->count();
        $this->assertEquals(1, $finalTicketCount);
    }

    /**
     * Test 10: Failed queue job sets import status to failed.
     */
    public function test_queue_job_failure_updates_import_status_to_failed(): void
    {
        $import = TicketImport::create([
            'file_name'       => 'nonexistent.csv',
            'file_path'       => 'imports/tickets/does_not_exist_' . Str::random(10) . '.csv',
            'user_id'         => $this->admin->id,
            'total_rows'      => 10,
            'processed_rows'  => 0,
            'successful_rows' => 0,
            'failed_rows'     => 0,
            'duplicate_rows'  => 0,
            'status'          => 'pending',
            'errors'          => [],
        ]);

        $job = new ProcessTicketImportJob($import->id);
        $job->handle(app(TicketImportService::class), app(\App\Repositories\Contracts\TicketImportRepositoryInterface::class));

        $import->refresh();
        $this->assertEquals('failed', $import->status);
    }

    /**
     * Test 11: getPaginatedImports() is a pure query and does NOT mutate stale import records.
     */
    public function test_get_paginated_imports_does_not_mutate_stale_records(): void
    {
        $staleImport = TicketImport::create([
            'file_name'       => 'stale.csv',
            'file_path'       => 'imports/tickets/stale.csv',
            'user_id'         => $this->admin->id,
            'total_rows'      => 5,
            'processed_rows'  => 0,
            'successful_rows' => 0,
            'failed_rows'     => 0,
            'duplicate_rows'  => 0,
            'status'          => 'processing',
            'errors'          => [],
        ]);

        // Manually backdate updated_at to 3 hours ago
        $staleImport->updated_at = now()->subHours(3);
        $staleImport->save(['timestamps' => false]);

        $repository = app(\App\Repositories\Contracts\TicketImportRepositoryInterface::class);
        $paginated = $repository->getPaginatedImports(15);

        $this->assertNotEmpty($paginated->items());

        // Refresh stale import from DB: it MUST remain 'processing' because getPaginatedImports must not mutate data
        $staleImport->refresh();
        $this->assertEquals('processing', $staleImport->status);
    }

    /**
     * Test 12: recoverStaleImports() explicitly marks stale pending/processing imports as failed.
     */
    public function test_explicit_stale_import_recovery_marks_stale_records_as_failed(): void
    {
        $staleImport = TicketImport::create([
            'file_name'       => 'stale_2.csv',
            'file_path'       => 'imports/tickets/stale_2.csv',
            'user_id'         => $this->admin->id,
            'total_rows'      => 5,
            'processed_rows'  => 0,
            'successful_rows' => 0,
            'failed_rows'     => 0,
            'duplicate_rows'  => 0,
            'status'          => 'processing',
            'errors'          => [],
        ]);

        $staleImport->updated_at = now()->subHours(3);
        $staleImport->save(['timestamps' => false]);

        $freshImport = TicketImport::create([
            'file_name'       => 'fresh.csv',
            'file_path'       => 'imports/tickets/fresh.csv',
            'user_id'         => $this->admin->id,
            'total_rows'      => 5,
            'processed_rows'  => 0,
            'successful_rows' => 0,
            'failed_rows'     => 0,
            'duplicate_rows'  => 0,
            'status'          => 'processing',
            'errors'          => [],
        ]);

        $service = app(TicketImportService::class);
        $recoveredCount = $service->recoverStaleImports(2);

        $this->assertGreaterThanOrEqual(1, $recoveredCount);

        $staleImport->refresh();
        $this->assertEquals('failed', $staleImport->status);
        $this->assertEquals('Timeout', $staleImport->errors[0]['type']);

        // Fresh import within 2 hours must NOT be affected
        $freshImport->refresh();
        $this->assertEquals('processing', $freshImport->status);
    }

    /**
     * Test 13: ProcessTicketImportJob middleware configuration ensures shared key, safe expiration, and dontRelease.
     */
    public function test_job_without_overlapping_middleware_configuration(): void
    {
        $job = new ProcessTicketImportJob(999);
        $middleware = $job->middleware();

        $this->assertCount(1, $middleware);
        $withoutOverlapping = $middleware[0];
        $this->assertInstanceOf(\Illuminate\Queue\Middleware\WithoutOverlapping::class, $withoutOverlapping);

        // Verify key, prefix, expiration, and release policy
        $this->assertEquals('999', $withoutOverlapping->key);
        $this->assertTrue($withoutOverlapping->shareKey);
        $this->assertNull($withoutOverlapping->releaseAfter); // dontRelease()
        $this->assertGreaterThanOrEqual(1800, $withoutOverlapping->expiresAfter);
    }
}
