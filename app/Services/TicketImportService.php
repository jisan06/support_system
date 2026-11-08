<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Models\TicketImport;
use App\Models\User;
use App\Repositories\Contracts\TicketImportRepositoryInterface;
use App\Repositories\Contracts\TicketRepositoryInterface;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketImportService
{
    protected TicketImportRepositoryInterface $repository;
    protected TicketService $ticketService;
    protected TicketRepositoryInterface $ticketRepository;

    public function __construct(
        TicketImportRepositoryInterface $repository,
        TicketService $ticketService,
        TicketRepositoryInterface $ticketRepository
    ) {
        $this->repository = $repository;
        $this->ticketService = $ticketService;
        $this->ticketRepository = $ticketRepository;
    }

    /**
     * Store uploaded CSV and initiate import tracking record.
     */
    public function initiateImport(UploadedFile $file, User $user): TicketImport
    {
        $storedPath = $file->store('imports/tickets', 'local');
        $fullPath = Storage::disk('local')->path($storedPath);
        $totalRows = $this->countCsvRows($fullPath);

        /** @var TicketImport $import */
        $import = $this->repository->create([
            'file_name'       => $file->getClientOriginalName(),
            'file_path'       => $storedPath,
            'user_id'         => $user->id,
            'total_rows'      => $totalRows,
            'processed_rows'  => 0,
            'successful_rows' => 0,
            'failed_rows'     => 0,
            'duplicate_rows'  => 0,
            'status'          => 'pending',
            'errors'          => [],
        ]);

        $this->logInitiatedActivity($import, $user);

        \App\Jobs\ProcessTicketImportJob::dispatch($import->id);

        return $import->fresh() ?? $import;
    }

    /**
     * Recover and mark stale pending or processing imports as failed.
     */
    public function recoverStaleImports(int $staleHours = 2): int
    {
        return $this->repository->markStaleImportsAsFailed($staleHours);
    }

    /**
     * Get a cache repository that supports atomic locking.
     * Checks default store, falls back to database store, or throws RuntimeException.
     */
    public function getLockRepository(): \Illuminate\Contracts\Cache\Repository
    {
        $defaultCache = Cache::store();
        if ($defaultCache->getStore() instanceof \Illuminate\Contracts\Cache\LockProvider) {
            return $defaultCache;
        }

        try {
            $databaseCache = Cache::store('database');
            if ($databaseCache->getStore() instanceof \Illuminate\Contracts\Cache\LockProvider) {
                return $databaseCache;
            }
        } catch (\Throwable $e) {
            // Database store not configured or unavailable
        }

        throw new \RuntimeException(
            'The configured cache store does not support atomic locks. Please configure a cache store that implements LockProvider (e.g. database, redis, memcached, or file).'
        );
    }

    /**
     * Process the ticket import in memory-efficient chunks.
     */
    public function processImport(TicketImport $ticketImport, int $chunkSize = 100): void
    {
        if ($ticketImport->exists) {
            $ticketImport->refresh();
        }

        // Idempotency check: if already completed, do not re-process
        if ($ticketImport->isCompleted()) {
            return;
        }

        $ticketImport->update(['status' => 'processing']);

        $fullPath = Storage::disk('local')->path($ticketImport->file_path);
        if (!file_exists($fullPath)) {
            $ticketImport->update([
                'status' => 'failed',
                'errors' => [
                    [
                        'row'     => 0,
                        'email'   => '',
                        'subject' => '',
                        'type'    => 'System Error',
                        'reason'  => 'Import file not found on disk: ' . $ticketImport->file_path,
                    ],
                ],
            ]);
            $this->logFailedActivity($ticketImport, 'Import file not found on disk.');
            return;
        }

        $handle = fopen($fullPath, 'r');
        if ($handle === false) {
            $ticketImport->update([
                'status' => 'failed',
                'errors' => [
                    [
                        'row'     => 0,
                        'email'   => '',
                        'subject' => '',
                        'type'    => 'System Error',
                        'reason'  => 'Unable to open CSV file for reading.',
                    ],
                ],
            ]);
            $this->logFailedActivity($ticketImport, 'Unable to open CSV file for reading.');
            return;
        }

        try {
            // Read and normalize headers
            $rawHeader = fgetcsv($handle);
            if ($rawHeader === false || empty($rawHeader)) {
                fclose($handle);
                $ticketImport->update([
                    'status' => 'failed',
                    'errors' => [
                        [
                            'row'     => 1,
                            'email'   => '',
                            'subject' => '',
                            'type'    => 'Header Error',
                            'reason'  => 'CSV file is empty or missing headers.',
                        ],
                    ],
                ]);
                $this->logFailedActivity($ticketImport, 'CSV file is empty or missing headers.');
                return;
            }

            $headerMap = $this->normalizeHeaders($rawHeader);
            $requiredHeaders = ['customer_email', 'subject', 'description'];
            $missing = [];
            foreach ($requiredHeaders as $req) {
                if (!isset($headerMap[$req])) {
                    $missing[] = $req;
                }
            }

            if (!empty($missing)) {
                fclose($handle);
                $reason = 'Missing required header column(s): ' . implode(', ', $missing) . '.';
                $ticketImport->update([
                    'status' => 'failed',
                    'errors' => [
                        [
                            'row'     => 1,
                            'email'   => '',
                            'subject' => '',
                            'type'    => 'Header Error',
                            'reason'  => $reason . ' Supported headers: customer_email, subject, description, priority, category, status, assigned_agent.',
                        ],
                    ],
                ]);
                $this->logFailedActivity($ticketImport, $reason);
                return;
            }

            $validCategories = $this->ticketService->getAvailableCategories();
            $validStatuses = ['open', 'in_progress', 'waiting_for_customer', 'resolved', 'closed'];
            $validPriorities = ['low', 'medium', 'high', 'urgent'];

            $uploader = $ticketImport->uploader ?? User::where('account_type', User::TYPE_ADMIN)->first();
            if (!$uploader) {
                $uploader = User::first();
            }

            $processedInBatch = [];
            $processedCount = $ticketImport->processed_rows;
            $successCount = $ticketImport->successful_rows;
            $failedCount = $ticketImport->failed_rows;
            $duplicateCount = $ticketImport->duplicate_rows;
            $errors = $ticketImport->errors ?? [];

            $currentRowIndex = 1; // 1 is header

            while (($row = fgetcsv($handle)) !== false) {
                $currentRowIndex++;

                // Skip completely empty rows
                if ($this->isEmptyRow($row)) {
                    continue;
                }

                // Resume support: skip already processed rows if resuming from a previous attempt
                if ($processedCount > 0 && ($currentRowIndex - 1) <= $processedCount) {
                    continue;
                }

                $customerEmail = trim($this->getColumnValue($row, $headerMap, 'customer_email'));
                $subject       = trim($this->getColumnValue($row, $headerMap, 'subject'));
                $description   = trim($this->getColumnValue($row, $headerMap, 'description'));
                $priorityRaw   = strtolower(trim($this->getColumnValue($row, $headerMap, 'priority', 'medium')));
                $categoryRaw   = trim($this->getColumnValue($row, $headerMap, 'category', ''));
                if ($categoryRaw === '') {
                    $categoryRaw = trim($this->getColumnValue($row, $headerMap, 'department', ''));
                }
                $statusRaw     = strtolower(trim($this->getColumnValue($row, $headerMap, 'status', 'open')));
                $agentEmail    = trim($this->getColumnValue($row, $headerMap, 'assigned_agent', ''));
                if ($agentEmail === '') {
                    $agentEmail = trim($this->getColumnValue($row, $headerMap, 'assigned_to', ''));
                }

                // 1. Validate Customer Email
                if (empty($customerEmail)) {
                    $failedCount++;
                    $processedCount++;
                    $this->recordRowError($ticketImport, [
                        'row'     => $currentRowIndex,
                        'email'   => '',
                        'subject' => $subject,
                        'type'    => 'Validation Error',
                        'reason'  => 'Customer email is required.',
                    ], $errors);
                    $this->saveProgressIfNeeded($ticketImport, $processedCount, $successCount, $failedCount, $duplicateCount, $errors, $chunkSize);
                    continue;
                }

                if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                    $failedCount++;
                    $processedCount++;
                    $this->recordRowError($ticketImport, [
                        'row'     => $currentRowIndex,
                        'email'   => $customerEmail,
                        'subject' => $subject,
                        'type'    => 'Validation Error',
                        'reason'  => "Invalid email format [{$customerEmail}].",
                    ], $errors);
                    $this->saveProgressIfNeeded($ticketImport, $processedCount, $successCount, $failedCount, $duplicateCount, $errors, $chunkSize);
                    continue;
                }

                $customer = User::where('email', $customerEmail)
                    ->where('account_type', User::TYPE_CUSTOMER)
                    ->first();

                if (!$customer) {
                    $failedCount++;
                    $processedCount++;
                    $this->recordRowError($ticketImport, [
                        'row'     => $currentRowIndex,
                        'email'   => $customerEmail,
                        'subject' => $subject,
                        'type'    => 'Customer Not Found',
                        'reason'  => "No active customer account found with email [{$customerEmail}].",
                    ], $errors);
                    $this->saveProgressIfNeeded($ticketImport, $processedCount, $successCount, $failedCount, $duplicateCount, $errors, $chunkSize);
                    continue;
                }

                // 2. Validate Subject
                if (empty($subject)) {
                    $failedCount++;
                    $processedCount++;
                    $this->recordRowError($ticketImport, [
                        'row'     => $currentRowIndex,
                        'email'   => $customerEmail,
                        'subject' => '',
                        'type'    => 'Validation Error',
                        'reason'  => 'Ticket subject is required.',
                    ], $errors);
                    $this->saveProgressIfNeeded($ticketImport, $processedCount, $successCount, $failedCount, $duplicateCount, $errors, $chunkSize);
                    continue;
                }

                if (mb_strlen($subject) > 255) {
                    $failedCount++;
                    $processedCount++;
                    $this->recordRowError($ticketImport, [
                        'row'     => $currentRowIndex,
                        'email'   => $customerEmail,
                        'subject' => mb_substr($subject, 0, 50) . '...',
                        'type'    => 'Validation Error',
                        'reason'  => 'Ticket subject exceeds maximum length of 255 characters.',
                    ], $errors);
                    $this->saveProgressIfNeeded($ticketImport, $processedCount, $successCount, $failedCount, $duplicateCount, $errors, $chunkSize);
                    continue;
                }

                // 3. Validate Description
                if (empty($description) || mb_strlen($description) < 2) {
                    $failedCount++;
                    $processedCount++;
                    $this->recordRowError($ticketImport, [
                        'row'     => $currentRowIndex,
                        'email'   => $customerEmail,
                        'subject' => $subject,
                        'type'    => 'Validation Error',
                        'reason'  => 'Ticket description is required and must be at least 2 characters.',
                    ], $errors);
                    $this->saveProgressIfNeeded($ticketImport, $processedCount, $successCount, $failedCount, $duplicateCount, $errors, $chunkSize);
                    continue;
                }

                // 4. Validate Priority
                $priority = !empty($priorityRaw) ? $priorityRaw : 'medium';
                if (!in_array($priority, $validPriorities, true)) {
                    $failedCount++;
                    $processedCount++;
                    $this->recordRowError($ticketImport, [
                        'row'     => $currentRowIndex,
                        'email'   => $customerEmail,
                        'subject' => $subject,
                        'type'    => 'Validation Error',
                        'reason'  => "Invalid priority [{$priorityRaw}]. Allowed: " . implode(', ', $validPriorities),
                    ], $errors);
                    $this->saveProgressIfNeeded($ticketImport, $processedCount, $successCount, $failedCount, $duplicateCount, $errors, $chunkSize);
                    continue;
                }

                // 5. Validate Category
                $category = null;
                if (!empty($categoryRaw)) {
                    $matchedCategory = null;
                    foreach ($validCategories as $validCat) {
                        if (strcasecmp($validCat, $categoryRaw) === 0) {
                            $matchedCategory = $validCat;
                            break;
                        }
                    }

                    if (!$matchedCategory) {
                        $failedCount++;
                        $processedCount++;
                        $this->recordRowError($ticketImport, [
                            'row'     => $currentRowIndex,
                            'email'   => $customerEmail,
                            'subject' => $subject,
                            'type'    => 'Validation Error',
                            'reason'  => "Invalid category [{$categoryRaw}]. Allowed: " . implode(', ', $validCategories),
                        ], $errors);
                        $this->saveProgressIfNeeded($ticketImport, $processedCount, $successCount, $failedCount, $duplicateCount, $errors, $chunkSize);
                        continue;
                    }
                    $category = $matchedCategory;
                }

                // 6. Validate Status (strictly open, in_progress, waiting_for_customer, resolved, closed)
                $status = !empty($statusRaw) ? $statusRaw : 'open';
                if (!in_array($status, $validStatuses, true)) {
                    $failedCount++;
                    $processedCount++;
                    $this->recordRowError($ticketImport, [
                        'row'     => $currentRowIndex,
                        'email'   => $customerEmail,
                        'subject' => $subject,
                        'type'    => 'Validation Error',
                        'reason'  => "Invalid status [{$statusRaw}]. Allowed: " . implode(', ', $validStatuses),
                    ], $errors);
                    $this->saveProgressIfNeeded($ticketImport, $processedCount, $successCount, $failedCount, $duplicateCount, $errors, $chunkSize);
                    continue;
                }

                // 7. Validate Assigned Agent (optional)
                $assignedToId = null;
                if (!empty($agentEmail)) {
                    $agent = User::where('email', $agentEmail)
                        ->where('account_type', User::TYPE_ADMIN)
                        ->first();

                    if (!$agent) {
                        $failedCount++;
                        $processedCount++;
                        $this->recordRowError($ticketImport, [
                            'row'     => $currentRowIndex,
                            'email'   => $customerEmail,
                            'subject' => $subject,
                            'type'    => 'Agent Not Found',
                            'reason'  => "Assigned agent [{$agentEmail}] not found or is not an active staff member.",
                        ], $errors);
                        $this->saveProgressIfNeeded($ticketImport, $processedCount, $successCount, $failedCount, $duplicateCount, $errors, $chunkSize);
                        continue;
                    }
                    $assignedToId = $agent->id;
                }

                // 8. Duplicate Detection Strategy (In-batch + DB across ALL statuses: open, in_progress, resolved, closed)
                $normalizedSubject = mb_strtolower(trim($subject));
                $dupKey = $customer->id . '|' . $normalizedSubject;

                if (isset($processedInBatch[$dupKey])) {
                    $duplicateCount++;
                    $processedCount++;
                    $this->recordRowError($ticketImport, [
                        'row'     => $currentRowIndex,
                        'email'   => $customerEmail,
                        'subject' => $subject,
                        'type'    => 'Duplicate',
                        'reason'  => 'Duplicate ticket detected within current import batch for this customer.',
                    ], $errors);
                    $this->saveProgressIfNeeded($ticketImport, $processedCount, $successCount, $failedCount, $duplicateCount, $errors, $chunkSize);
                    continue;
                }

                // Concurrency Protection via Atomic Lock with safe 120s timeout
                $lockKey = "ticket_import_lock:{$customer->id}:" . md5($normalizedSubject);
                $lockLifetime = 120; // Safe duration preventing expiration during creation & DB transaction
                $lockWait = 15;     // Wait up to 15s for concurrent worker on same customer/subject to finish

                $lockRepo = $this->getLockRepository();
                $lock = $lockRepo->lock($lockKey, $lockLifetime);

                try {
                    $lock->block($lockWait, function () use (
                        $customer,
                        $normalizedSubject,
                        $assignedToId,
                        $subject,
                        $description,
                        $priority,
                        $category,
                        $status,
                        $uploader,
                        &$processedInBatch,
                        $dupKey,
                        &$successCount,
                        &$duplicateCount,
                        &$processedCount,
                        &$failedCount,
                        &$errors,
                        $ticketImport,
                        $currentRowIndex,
                        $customerEmail
                    ) {
                        // Check DB across all ticket statuses
                        $dbDuplicate = Ticket::where('customer_id', $customer->id)
                            ->whereRaw('LOWER(TRIM(subject)) = ?', [$normalizedSubject])
                            ->first();

                        if ($dbDuplicate) {
                            $duplicateCount++;
                            $processedCount++;
                            $this->recordRowError($ticketImport, [
                                'row'     => $currentRowIndex,
                                'email'   => $customerEmail,
                                'subject' => $subject,
                                'type'    => 'Duplicate',
                                'reason'  => "Duplicate ticket detected (ticket #{$dbDuplicate->ticket_number} with status '{$dbDuplicate->status}' already exists for this customer).",
                            ], $errors);
                            return;
                        }

                        // Create Ticket within database transaction
                        DB::transaction(function () use ($customer, $assignedToId, $subject, $description, $priority, $category, $status, $uploader) {
                            $this->ticketService->createTicket([
                                'customer_id' => $customer->id,
                                'assigned_to' => $assignedToId,
                                'subject'     => $subject,
                                'description' => $description,
                                'priority'    => $priority,
                                'department'  => $category,
                                'status'      => $status,
                                'auto_assign' => false,
                            ], $uploader);
                        });

                        $processedInBatch[$dupKey] = true;
                        $successCount++;
                        $processedCount++;
                    });
                } catch (LockTimeoutException $e) {
                    $failedCount++;
                    $processedCount++;
                    $this->recordRowError($ticketImport, [
                        'row'     => $currentRowIndex,
                        'email'   => $customerEmail,
                        'subject' => $subject,
                        'type'    => 'Lock Timeout',
                        'reason'  => 'Unable to acquire concurrency lock to process this ticket.',
                    ], $errors);
                } catch (\Throwable $e) {
                    $failedCount++;
                    $processedCount++;
                    $this->recordRowError($ticketImport, [
                        'row'     => $currentRowIndex,
                        'email'   => $customerEmail,
                        'subject' => $subject,
                        'type'    => 'Creation Error',
                        'reason'  => 'Database error: ' . $e->getMessage(),
                    ], $errors);
                }

                $this->saveProgressIfNeeded($ticketImport, $processedCount, $successCount, $failedCount, $duplicateCount, $errors, $chunkSize);
            }

            fclose($handle);

            // Mark as completed
            $ticketImport->update([
                'status'          => 'completed',
                'total_rows'      => max($ticketImport->total_rows, $processedCount),
                'processed_rows'  => $processedCount,
                'successful_rows' => $successCount,
                'failed_rows'     => $failedCount,
                'duplicate_rows'  => $duplicateCount,
                'errors'          => array_slice($errors, 0, 50),
            ]);

            $this->logCompletedActivity($ticketImport);
        } catch (\Throwable $e) {
            if (is_resource($handle)) {
                fclose($handle);
            }

            $this->recordRowError($ticketImport, [
                'row'     => 0,
                'email'   => '',
                'subject' => '',
                'type'    => 'Fatal Error',
                'reason'  => 'Import failed: ' . $e->getMessage(),
            ], $errors);

            $ticketImport->update([
                'status' => 'failed',
                'errors' => array_slice($errors, 0, 50),
            ]);

            $this->logFailedActivity($ticketImport, 'Import failed: ' . $e->getMessage());

            throw $e;
        }
    }

    /**
     * Downloadable sample CSV template.
     */
    public function generateTemplateCsv(): StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="ticket_import_template.csv"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'customer_email',
                'subject',
                'description',
                'priority',
                'category',
                'status',
                'assigned_agent',
            ]);

            fputcsv($handle, [
                'customer@example.com',
                'Unable to access billing dashboard',
                'Customer is receiving a 403 Forbidden error when trying to view invoices.',
                'high',
                'Billing & Payments',
                'open',
                'agent@example.com',
            ]);

            fputcsv($handle, [
                'client@example.com',
                'VPN connection timeout',
                'VPN server times out after entering credentials from home office.',
                'urgent',
                'Technical Support',
                'open',
                '',
            ]);

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Downloadable error report containing failed rows, duplicates, and reasons.
     * Streams directly from disk file to prevent memory exhaustion on large error counts.
     */
    public function generateErrorReportCsv(TicketImport $ticketImport): StreamedResponse
    {
        $fileName = 'import_error_report_' . $ticketImport->id . '.csv';
        $diskPath = "imports/tickets/errors_{$ticketImport->id}.csv";

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($ticketImport, $diskPath) {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            if (Storage::disk('local')->exists($diskPath)) {
                $fullPath = Storage::disk('local')->path($diskPath);
                $file = fopen($fullPath, 'r');
                if ($file !== false) {
                    while (($line = fgets($file)) !== false) {
                        fwrite($output, $line);
                    }
                    fclose($file);
                }
            } else {
                fputcsv($output, [
                    'Row Number',
                    'Customer Email',
                    'Subject',
                    'Error Type',
                    'Reason',
                ]);

                $errors = $ticketImport->errors ?? [];
                foreach ($errors as $error) {
                    fputcsv($output, [
                        $error['row'] ?? 'N/A',
                        $error['email'] ?? '',
                        $error['subject'] ?? '',
                        $error['type'] ?? 'Error',
                        $error['reason'] ?? 'Unknown error',
                    ]);
                }
            }

            fclose($output);
        }, 200, $headers);
    }

    /**
     * Record row error both to disk CSV (for scalable streaming) and preview memory array.
     */
    protected function recordRowError(TicketImport $ticketImport, array $errorItem, array &$errorsList): void
    {
        // Add to preview list (capped at 50 to prevent JSON column bloat in DB)
        if (count($errorsList) < 50) {
            $errorsList[] = $errorItem;
        }

        // Stream to disk error CSV file
        $diskPath = "imports/tickets/errors_{$ticketImport->id}.csv";
        $fullPath = Storage::disk('local')->path($diskPath);

        $exists = file_exists($fullPath);
        $file = fopen($fullPath, 'a');
        if ($file !== false) {
            if (!$exists) {
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($file, ['Row Number', 'Customer Email', 'Subject', 'Error Type', 'Reason']);
            }
            fputcsv($file, [
                $errorItem['row'] ?? 'N/A',
                $errorItem['email'] ?? '',
                $errorItem['subject'] ?? '',
                $errorItem['type'] ?? 'Error',
                $errorItem['reason'] ?? '',
            ]);
            fclose($file);
        }
    }

    /**
     * Count rows in CSV file without loading entire file into memory.
     */
    protected function countCsvRows(string $fullPath): int
    {
        if (!file_exists($fullPath)) {
            return 0;
        }

        $lineCount = 0;
        $handle = fopen($fullPath, 'r');
        if ($handle === false) {
            return 0;
        }

        // Skip header
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return 0;
        }

        while (($row = fgetcsv($handle)) !== false) {
            if (!$this->isEmptyRow($row)) {
                $lineCount++;
            }
        }

        fclose($handle);
        return $lineCount;
    }

    /**
     * Normalize header row into lower-snake-cased index map.
     */
    protected function normalizeHeaders(array $rawHeader): array
    {
        $map = [];
        foreach ($rawHeader as $index => $col) {
            $clean = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', trim((string) $col));
            $clean = Str::snake(strtolower(trim($clean)));
            if ($clean !== '') {
                $map[$clean] = $index;
            }
        }
        return $map;
    }

    /**
     * Get cell value safely from row by header key.
     */
    protected function getColumnValue(array $row, array $headerMap, string $key, string $default = ''): string
    {
        if (!isset($headerMap[$key])) {
            return $default;
        }

        $index = $headerMap[$key];
        return isset($row[$index]) ? (string) $row[$index] : $default;
    }

    /**
     * Check if a CSV row is completely empty.
     */
    protected function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }
        return true;
    }

    /**
     * Periodically save import progress to DB.
     */
    protected function saveProgressIfNeeded(
        TicketImport $ticketImport,
        int $processed,
        int $success,
        int $failed,
        int $duplicate,
        array $errors,
        int $chunkSize
    ): void {
        if ($processed % $chunkSize === 0) {
            $ticketImport->update([
                'processed_rows'  => $processed,
                'successful_rows' => $success,
                'failed_rows'     => $failed,
                'duplicate_rows'  => $duplicate,
                'errors'          => array_slice($errors, 0, 50),
            ]);
        }
    }

    /**
     * Record import initiation activity log.
     */
    protected function logInitiatedActivity(TicketImport $import, User $user): void
    {
        $this->logActivitySafely(function () use ($import, $user) {
            activity('ticket_import')
                ->causedBy($user)
                ->performedOn($import)
                ->event('initiated')
                ->withProperties([
                    'import_id'  => $import->id,
                    'file_name'  => $import->file_name,
                    'total_rows' => $import->total_rows,
                ])
                ->log("Ticket CSV import initiated for file '{$import->file_name}' ({$import->total_rows} rows)");
        });
    }

    /**
     * Record import completion activity log with final counts.
     * Prevents duplicate completion activity during retries.
     */
    public function logCompletedActivity(TicketImport $ticketImport): void
    {
        $this->logActivitySafely(function () use ($ticketImport) {
            $alreadyLogged = ActivityLog::where('log_name', 'ticket_import')
                ->where('subject_type', TicketImport::class)
                ->where('subject_id', $ticketImport->id)
                ->where('event', 'completed')
                ->exists();

            if ($alreadyLogged) {
                return;
            }

            $activity = activity('ticket_import')
                ->performedOn($ticketImport)
                ->event('completed')
                ->withProperties([
                    'import_id'       => $ticketImport->id,
                    'total_rows'      => $ticketImport->total_rows,
                    'successful_rows' => $ticketImport->successful_rows,
                    'duplicate_rows'  => $ticketImport->duplicate_rows,
                    'failed_rows'     => $ticketImport->failed_rows,
                ]);

            if (auth()->check()) {
                $activity->causedBy(auth()->user());
            }

            $activity->log("Ticket CSV import #{$ticketImport->id} completed ({$ticketImport->successful_rows} successful, {$ticketImport->duplicate_rows} duplicates, {$ticketImport->failed_rows} failed)");
        });
    }

    /**
     * Record import failure activity log with safe sanitized summary.
     * Prevents duplicate failure activity during retries.
     */
    public function logFailedActivity(TicketImport $ticketImport, string $reason): void
    {
        $this->logActivitySafely(function () use ($ticketImport, $reason) {
            $alreadyLogged = ActivityLog::where('log_name', 'ticket_import')
                ->where('subject_type', TicketImport::class)
                ->where('subject_id', $ticketImport->id)
                ->where('event', 'failed')
                ->exists();

            if ($alreadyLogged) {
                return;
            }

            $sanitizedReason = $this->sanitizeFailureReason($reason);
            $processedRows = (int) $ticketImport->processed_rows;
            $totalRows = (int) $ticketImport->total_rows;
            $remainingRows = max(0, $totalRows - $processedRows);

            $activity = activity('ticket_import')
                ->performedOn($ticketImport)
                ->event('failed')
                ->withProperties([
                    'import_id'      => $ticketImport->id,
                    'failure_reason' => $sanitizedReason,
                    'processed_rows' => $processedRows,
                    'remaining_rows' => $remainingRows,
                    'total_rows'     => $totalRows,
                ]);

            if (auth()->check()) {
                $activity->causedBy(auth()->user());
            }

            $activity->log("Ticket CSV import #{$ticketImport->id} failed: {$sanitizedReason}");
        });
    }

    /**
     * Sanitize failure reason to prevent leaking server paths, SQL traces, or credentials.
     */
    protected function sanitizeFailureReason(string $reason): string
    {
        // Strip absolute Windows and Unix file paths
        $sanitized = preg_replace('/[A-Za-z]:\\\\[^\s,:]+/', '[file_path]', $reason);
        $sanitized = preg_replace('/\/[^\s,:]+\/[^\s,:]+/', '[file_path]', $sanitized);
        $sanitized = trim(preg_replace('/\s+/', ' ', $sanitized));

        return Str::limit($sanitized, 250);
    }

    /**
     * Safely execute activity logging so logging failures never break core import execution.
     */
    protected function logActivitySafely(callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            Log::warning("Failed to record ticket import activity log: " . $e->getMessage());
        }
    }
}
