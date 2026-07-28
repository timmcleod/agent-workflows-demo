<?php

namespace App\Console\Commands;

use App\AgentWorkflows\ContractReview;
use App\Support\FakeAgents;
use Illuminate\Console\Command;
use Throwable;
use TimMcLeod\AgentWorkflows\Facades\AgentWorkflow;
use TimMcLeod\AgentWorkflows\Models\WorkflowRun;

class DemoSeed extends Command
{
    protected $signature = 'demo:seed';

    protected $description = 'Seed the dashboard with runs in every interesting state (uses fake agents, runs inline)';

    protected const LOW_RISK_CONTRACT = <<<'CONTRACT'
    SERVICES AGREEMENT — The Supplier shall provide consulting services to the
    Client for a fee of $80,000 per year. The Supplier's liability is capped at
    twelve months of fees. All intellectual property created under this agreement
    is assigned to the Client. Either party may terminate for convenience with
    60 days notice.
    CONTRACT;

    protected const HIGH_RISK_CONTRACT = <<<'CONTRACT'
    SERVICES AGREEMENT — The Supplier shall provide consulting services to the
    Client for a fee of $200,000 per year, auto-renewing annually unless either
    party gives 90 days notice. The Supplier's total liability is unlimited.
    All intellectual property created under this agreement is assigned to the
    Supplier. The Client indemnifies the Supplier against all third-party claims.
    Either party may terminate for convenience with 5 days notice.
    CONTRACT;

    public function handle(): int
    {
        FakeAgents::register();
        config(['queue.default' => 'sync']);

        // 1. Completed: low risk → auto-approve branch → approved → summary.
        $run = $this->start(self::LOW_RISK_CONTRACT);
        $run->resume(['approved' => true, 'notes' => 'Standard terms, cleared for signature.']);
        $this->line("✔ completed (auto-approve path):  {$run->id}");

        // 2. Completed: high risk → escalation branch → rejected → summary.
        $run = $this->start(self::HIGH_RISK_CONTRACT);
        $run->resume(['approved' => false, 'notes' => 'Unlimited liability is a non-starter; renegotiate.']);
        $this->line("✔ completed (escalated, rejected): {$run->id}");

        // 3. Parked: high risk → escalation branch → awaiting sign-off.
        $run = $this->start(self::HIGH_RISK_CONTRACT);
        $this->line("✋ awaiting human sign-off:         {$run->id}");

        // 4. Failed: simulated outage at the enrichment step.
        $run = $this->start(self::HIGH_RISK_CONTRACT, simulateFailure: true);
        $this->line("✕ failed at EnrichmentStep:        {$run->id}");

        // 5. Cancelled: parked, then withdrawn.
        $run = $this->start(self::LOW_RISK_CONTRACT)->cancel();
        $this->line("– cancelled:                       {$run->id}");

        $this->newLine();
        $this->info('Seeded. Open the dashboard: /agent-workflows');

        return self::SUCCESS;
    }

    protected function start(string $contract, bool $simulateFailure = false): WorkflowRun
    {
        try {
            return AgentWorkflow::start(ContractReview::class, input: [
                'contract' => $contract,
                'simulate_failure' => $simulateFailure,
            ]);
        } catch (Throwable) {
            // The sync queue unwinds the simulated outage into the caller;
            // the run itself is already checkpointed as failed.
            return WorkflowRun::latest('id')->firstOrFail();
        }
    }
}
