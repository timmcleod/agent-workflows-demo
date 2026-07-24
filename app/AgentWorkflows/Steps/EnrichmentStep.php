<?php

namespace App\AgentWorkflows\Steps;

use RuntimeException;
use TimMcLeod\AgentWorkflows\WorkflowState;

/**
 * Stands in for a fallible integration (a document service, a CRM). Started
 * with --fail, it throws — so you can watch the run fail at THIS step and
 * then retry from THIS step, with the two agent steps before it untouched.
 */
class EnrichmentStep
{
    public function __invoke(WorkflowState $state): WorkflowState
    {
        if ($state->get('simulate_failure')) {
            throw new RuntimeException('Simulated enrichment outage (run demo:retry to recover).');
        }

        return $state->set('enriched_at', now()->toIso8601String());
    }
}
