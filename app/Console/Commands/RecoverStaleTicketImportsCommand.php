<?php

namespace App\Console\Commands;

use App\Services\TicketImportService;
use Illuminate\Console\Command;

class RecoverStaleTicketImportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:recover-stale-imports {--hours=2 : Number of hours after which pending/processing imports are considered stale}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recover and mark stale pending or processing ticket imports as failed';

    /**
     * Execute the console command.
     */
    public function handle(TicketImportService $service): int
    {
        $hours = (int) $this->option('hours');
        if ($hours < 1) {
            $hours = 2;
        }

        $this->info("Checking for stale ticket imports older than {$hours} hour(s)...");

        $recovered = $service->recoverStaleImports($hours);

        $this->info("Recovered {$recovered} stale ticket import(s).");

        return self::SUCCESS;
    }
}

