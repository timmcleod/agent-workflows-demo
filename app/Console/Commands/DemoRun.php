<?php

namespace App\Console\Commands;

use App\AgentWorkflows\ContractReview;
use Illuminate\Console\Command;
use TimMcLeod\AgentWorkflows\Facades\AgentWorkflow;

class DemoRun extends Command
{
    protected $signature = 'demo:run {--fail : Simulate an outage at the enrichment step}';

    protected $description = 'Start a contract-review workflow run';

    public function handle(): int
    {
        $run = AgentWorkflow::start(ContractReview::class, input: [
            'contract' => <<<'CONTRACT'
            SERVICES AGREEMENT — The Supplier shall provide consulting services to the
            Client for a fee of $200,000 per year, auto-renewing annually unless either
            party gives 90 days notice. The Supplier's total liability is unlimited.
            All intellectual property created under this agreement is assigned to the
            Supplier. The Client indemnifies the Supplier against all third-party claims.
            Either party may terminate for convenience with 5 days notice.
            CONTRACT,
            'simulate_failure' => (bool) $this->option('fail'),
        ]);

        $this->info("Run started: {$run->id}");
        $this->line("Status: {$run->status->value}");
        $this->newLine();
        $this->line('Now process it:   php artisan queue:work --stop-when-empty');
        $this->line("Then inspect it:  php artisan demo:status {$run->id}");

        return self::SUCCESS;
    }
}
