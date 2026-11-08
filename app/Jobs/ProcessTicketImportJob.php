<?php

namespace App\Jobs;

use App\Models\TicketImport;
use App\Repositories\Contracts\TicketImportRepositoryInterface;
use App\Services\TicketImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class ProcessTicketImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $importId;
    public int $tries = 3;
    public int $timeout = 1800; // 30 minutes for large imports

    /**
     * Create a new job instance.
     */
    public function __construct(int $importId)
    {
        $this->importId = $importId;
    }

    /**
     * Get the middleware the job should pass through.
     * Prevents overlapping / concurrent execution of the same import.
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping((string) $this->importId))
                ->shared()
                ->dontRelease()
                ->expireAfter(max(1800, $this->timeout + 300)),
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(TicketImportService $service, TicketImportRepositoryInterface $repository): void
    {
        $ticketImport = $repository->find($this->importId);
        if (!$ticketImport || $ticketImport->isCompleted()) {
            return;
        }

        try {
            $service->processImport($ticketImport);
        } catch (\Throwable $e) {
            $this->failed($e);
            throw $e;
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(?\Throwable $exception = null): void
    {
        $ticketImport = TicketImport::find($this->importId);
        if ($ticketImport && !$ticketImport->isCompleted()) {
            $errors = $ticketImport->errors ?? [];
            $errors[] = [
                'row'     => 0,
                'email'   => '',
                'subject' => '',
                'type'    => 'System Error',
                'reason'  => 'Import job failed: ' . ($exception ? $exception->getMessage() : 'Unknown error'),
            ];

            $ticketImport->update([
                'status' => 'failed',
                'errors' => array_slice($errors, 0, 50),
            ]);
        }
    }
}
