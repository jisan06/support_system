<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
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

class TicketImportActivityLogTest extends TestCase
{
    use DatabaseTransactions, CreatesAdminUser;

    protected User $admin;
    protected User $customer;
    protected TicketImportService $importService;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->importService = app(TicketImportService::class);

        $this->admin = $this->createAdminUser(['create_ticket', 'view_ticket']);

        $this->customer = User::create([
            'name'              => 'Import Customer',
            'email'             => 'import_cust_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'account_type'      => User::TYPE_CUSTOMER,
            'status'            => 'Active',
            'email_verified_at' => now(),
        ]);
    }

    protected function createCsvFile(string $name, array $rows): UploadedFile
    {
        $f = fopen('php://memory', 'r+');
        foreach ($rows as $row) {
            fputcsv($f, $row);
        }
        rewind($f);
        $content = stream_get_contents($f);
        fclose($f);

        return UploadedFile::fake()->createWithContent($name, $content);
    }

    /**
     * Test 1: Import initiation creates an activity with the correct causer and metadata.
     */
    public function test_import_initiation_creates_activity_with_causer_and_metadata(): void
    {
        $this->actingAs($this->admin);

        $file = $this->createCsvFile('initiate_test.csv', [
            ['customer_email', 'subject', 'description', 'priority', 'category', 'status', 'assigned_agent'],
            [$this->customer->email, 'Initiate Subject 1', 'Description text 1', 'medium', '', 'open', ''],
            [$this->customer->email, 'Initiate Subject 2', 'Description text 2', 'high', '', 'open', ''],
        ]);

        $import = $this->importService->initiateImport($file, $this->admin);

        $activity = ActivityLog::where('log_name', 'ticket_import')
            ->where('subject_type', TicketImport::class)
            ->where('subject_id', $import->id)
            ->where('event', 'initiated')
            ->first();

        $this->assertNotNull($activity, 'Initiation activity log was not recorded.');
        $this->assertEquals($this->admin->id, $activity->causer_id);
        $this->assertEquals($this->admin->name, $activity->causer_name);
        $this->assertEquals('TicketImport', $activity->subject_label);

        $properties = $activity->properties;
        $this->assertEquals($import->id, $properties['import_id'] ?? null);
        $this->assertEquals('initiate_test.csv', $properties['file_name'] ?? null);
        $this->assertEquals(2, $properties['total_rows'] ?? null);
        $this->assertStringContainsString('initiate_test.csv', $activity->description);
    }

    /**
     * Test 2: Successful import records final row counts.
     */
    public function test_successful_import_records_final_row_counts(): void
    {
        $this->actingAs($this->admin);

        // Pre-create a duplicate ticket in DB
        Ticket::create([
            'ticket_number' => 'TCK-PRE-' . strtoupper(Str::random(4)),
            'customer_id'   => $this->customer->id,
            'subject'       => 'Existing Ticket Subject',
            'status'        => 'open',
            'priority'      => 'medium',
        ]);

        $file = $this->createCsvFile('success_lifecycle.csv', [
            ['customer_email', 'subject', 'description', 'priority', 'category', 'status', 'assigned_agent'],
            // 1. Success row
            [$this->customer->email, 'Fresh Unique Subject', 'Valid description.', 'low', '', 'open', ''],
            // 2. Duplicate row (matches existing DB ticket)
            [$this->customer->email, 'Existing Ticket Subject', 'Duplicate text.', 'low', '', 'open', ''],
            // 3. Invalid row (missing subject)
            [$this->customer->email, '', 'Missing subject text.', 'low', '', 'open', ''],
        ]);

        $import = $this->importService->initiateImport($file, $this->admin);
        $this->importService->processImport($import);

        $activity = ActivityLog::where('log_name', 'ticket_import')
            ->where('subject_type', TicketImport::class)
            ->where('subject_id', $import->id)
            ->where('event', 'completed')
            ->first();

        $this->assertNotNull($activity, 'Completion activity log was not recorded.');

        $properties = $activity->properties;
        $this->assertEquals($import->id, $properties['import_id'] ?? null);
        $this->assertEquals(3, $properties['total_rows'] ?? null);
        $this->assertEquals(1, $properties['successful_rows'] ?? null);
        $this->assertEquals(1, $properties['duplicate_rows'] ?? null);
        $this->assertEquals(1, $properties['failed_rows'] ?? null);

        $this->assertStringContainsString('1 successful', $activity->description);
        $this->assertStringContainsString('1 duplicates', $activity->description);
        $this->assertStringContainsString('1 failed', $activity->description);
    }

    /**
     * Test 3: Failed import records a safe sanitized failure summary without server paths.
     */
    public function test_failed_import_records_safe_failure_summary(): void
    {
        $this->actingAs($this->admin);

        $file = $this->createCsvFile('missing_headers.csv', [
            ['customer_email', 'priority'], // Missing required 'subject' and 'description'
            [$this->customer->email, 'high'],
        ]);

        $import = $this->importService->initiateImport($file, $this->admin);
        $this->importService->processImport($import);

        $activity = ActivityLog::where('log_name', 'ticket_import')
            ->where('subject_type', TicketImport::class)
            ->where('subject_id', $import->id)
            ->where('event', 'failed')
            ->first();

        $this->assertNotNull($activity, 'Failure activity log was not recorded.');

        $properties = $activity->properties;
        $this->assertEquals($import->id, $properties['import_id'] ?? null);
        $this->assertNotEmpty($properties['failure_reason']);
        $this->assertStringContainsString('Missing required header', $properties['failure_reason']);

        // Check path sanitization: no internal file system drive roots should be leaked
        $this->assertStringNotContainsString('F:\\', $properties['failure_reason']);
        $this->assertStringNotContainsString('/var/', $properties['failure_reason']);
    }

    /**
     * Test 4: Retrying or repeating processing does not create duplicate terminal activities.
     */
    public function test_retrying_does_not_create_duplicate_terminal_activities(): void
    {
        $this->actingAs($this->admin);

        $file = $this->createCsvFile('dedup_activities.csv', [
            ['customer_email', 'subject', 'description', 'priority', 'category', 'status', 'assigned_agent'],
            [$this->customer->email, 'Deduplication Test Subject', 'Valid description.', 'low', '', 'open', ''],
        ]);

        $import = $this->importService->initiateImport($file, $this->admin);

        // Run 1: Normal processing completion
        $this->importService->processImport($import);

        $completedCount1 = ActivityLog::where('log_name', 'ticket_import')
            ->where('subject_type', TicketImport::class)
            ->where('subject_id', $import->id)
            ->where('event', 'completed')
            ->count();
        $this->assertEquals(1, $completedCount1);

        // Run 2: Simulate duplicate execution or retry call
        $this->importService->logCompletedActivity($import);
        $this->importService->logCompletedActivity($import);

        $completedCount2 = ActivityLog::where('log_name', 'ticket_import')
            ->where('subject_type', TicketImport::class)
            ->where('subject_id', $import->id)
            ->where('event', 'completed')
            ->count();

        $this->assertEquals(1, $completedCount2, 'Duplicate completed activities were recorded for the same import.');
    }

    /**
     * Test 5: Activity logging does not expose sensitive data (passwords, CSV contents, PII).
     */
    public function test_activity_logging_does_not_expose_sensitive_data(): void
    {
        $secretSensitiveDescription = 'SUPER_SENSITIVE_CUSTOMER_CREDIT_CARD_DATA_1234';

        $file = $this->createCsvFile('privacy_import.csv', [
            ['customer_email', 'subject', 'description', 'priority', 'category', 'status', 'assigned_agent'],
            [$this->customer->email, 'Privacy Test Subject', $secretSensitiveDescription, 'low', '', 'open', ''],
        ]);

        $import = $this->importService->initiateImport($file, $this->admin);
        $this->importService->processImport($import);

        $activities = ActivityLog::where('log_name', 'ticket_import')
            ->where('subject_type', TicketImport::class)
            ->where('subject_id', $import->id)
            ->get();

        $this->assertNotEmpty($activities);

        foreach ($activities as $act) {
            $rawJson = json_encode($act->properties);
            $this->assertStringNotContainsString(
                $secretSensitiveDescription,
                $rawJson,
                'Sensitive description from CSV row was leaked into activity log properties.'
            );
            $this->assertStringNotContainsString(
                $secretSensitiveDescription,
                $act->description,
                'Sensitive description from CSV row was leaked into activity log description.'
            );
        }
    }
}

