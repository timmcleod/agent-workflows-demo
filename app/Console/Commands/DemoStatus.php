<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use TimMcLeod\AgentWorkflows\Models\WorkflowRun;

class DemoStatus extends Command
{
    protected $signature = 'demo:status {run : The run id}';

    protected $description = 'Show a workflow run, its step audit log, and any open interrupt';

    public function handle(): int
    {
        $run = WorkflowRun::findOrFail($this->argument('run'));

        $this->info("Run {$run->id} — {$run->name}");
        $this->line("Status:       {$run->status->value}");
        $this->line("Current step: {$run->current_step}");

        if ($run->failed_step !== null) {
            $this->line("Failed step:  {$run->failed_step}");
            $this->line("Reason:       {$run->failure_reason}");
        }

        $this->newLine();

        $this->table(
            ['Step', 'Type', 'Status', 'Attempt', 'Tokens (in/out)', 'Finished'],
            $run->steps()->orderBy('id')->get()->map(fn ($step) => [
                $step->step_id,
                $step->type->value,
                $step->status->value,
                $step->attempt,
                ($step->usage['prompt_tokens'] ?? '—').' / '.($step->usage['completion_tokens'] ?? '—'),
                $step->finished_at?->toTimeString() ?? '—',
            ]),
        );

        $interrupt = $run->interrupts()->whereNull('resolved_at')->latest('id')->first();

        if ($interrupt !== null) {
            $this->warn("Awaiting: {$interrupt->reason}");
            $this->line('Expected payload: '.json_encode($interrupt->response_schema ?? $interrupt->context));
            $this->line("Resolve with:     php artisan demo:approve {$run->id}");
        }

        if ($run->status->value === 'failed') {
            $this->line("Recover with:     php artisan demo:retry {$run->id}");
        }

        $this->newLine();
        $this->line('State: '.json_encode($run->state, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
