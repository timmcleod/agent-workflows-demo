<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use TimMcLeod\AgentWorkflows\Models\WorkflowRun;

class DemoRetry extends Command
{
    protected $signature = 'demo:retry {run : The run id}';

    protected $description = 'Heal the simulated outage and retry the run from its failed step';

    public function handle(): int
    {
        $run = WorkflowRun::findOrFail($this->argument('run'));

        // "Fix the outage": flip the flag inside the checkpointed state.
        $run->update(['state' => array_merge($run->state, ['simulate_failure' => false])]);

        $run = $run->retry();

        $this->info("Retrying from [{$run->current_step}]. Status: {$run->status->value}");
        $this->line('Run the worker: php artisan queue:work --stop-when-empty');
        $this->line('Note the earlier agent steps do NOT re-run — check demo:status attempts.');

        return self::SUCCESS;
    }
}
