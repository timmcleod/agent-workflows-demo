<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use TimMcLeod\AgentWorkflows\Models\WorkflowRun;

class DemoApprove extends Command
{
    protected $signature = 'demo:approve {run : The run id} {--reject} {--notes=}';

    protected $description = 'Resume a run that is awaiting human sign-off';

    public function handle(): int
    {
        $run = WorkflowRun::findOrFail($this->argument('run'));

        $run = $run->resume([
            'approved' => ! $this->option('reject'),
            'notes' => $this->option('notes'),
        ]);

        $this->info("Resumed. Status: {$run->status->value}");
        $this->line('Run the worker to finish: php artisan queue:work --stop-when-empty');

        return self::SUCCESS;
    }
}
