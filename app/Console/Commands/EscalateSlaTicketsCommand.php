<?php

namespace App\Console\Commands;

use App\Services\SlaEscalationService;
use Illuminate\Console\Command;

class EscalateSlaTicketsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ticket:escalate-sla {--chunk=50 : The number of tickets to process per chunk}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically detect SLA breaches on active tickets and execute configured escalations';

    /**
     * Execute the console command.
     */
    public function handle(SlaEscalationService $escalationService): int
    {
        $this->info('Starting SLA escalation check...');

        $chunkSize = (int) $this->option('chunk');
        $stats = $escalationService->processEscalations($chunkSize);

        $this->info("SLA escalation processing complete.");
        $this->table(
            ['Active Tickets Checked', 'Escalations Executed', 'Errors'],
            [[$stats['checked'], $stats['escalated'], $stats['errors']]]
        );

        return self::SUCCESS;
    }
}

