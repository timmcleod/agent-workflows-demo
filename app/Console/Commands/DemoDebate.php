<?php

namespace App\Console\Commands;

use App\AgentWorkflows\ContractDebate;
use Illuminate\Console\Command;

class DemoDebate extends Command
{
    protected $signature = 'demo:debate';

    protected $description = 'Start a contract-debate workflow run (advocate vs skeptic, judged each round)';

    public function handle(): int
    {
        $run = ContractDebate::start([
            'contract' => <<<'CONTRACT'
            SERVICES AGREEMENT — The Supplier shall provide consulting services to the
            Client for a fee of $200,000 per year, auto-renewing annually unless either
            party gives 90 days notice. The Supplier's total liability is unlimited.
            All intellectual property created under this agreement is assigned to the
            Supplier. The Client indemnifies the Supplier against all third-party claims.
            Either party may terminate for convenience with 5 days notice.
            CONTRACT,
        ]);

        $this->info("Debate started: {$run->id}");
        $this->line("Status: {$run->status->value}");
        $this->newLine();
        $this->line('Now process it:   php artisan queue:work --stop-when-empty');
        $this->line("Then inspect it:  php artisan demo:status {$run->id}");

        return self::SUCCESS;
    }
}
