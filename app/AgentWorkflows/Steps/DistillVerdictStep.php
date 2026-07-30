<?php

namespace App\AgentWorkflows\Steps;

use TimMcLeod\AgentWorkflows\Support\Transcript;
use TimMcLeod\AgentWorkflows\WorkflowState;

/**
 * Reads the debate's outcome into top-level state keys — the downstream
 * pattern for consuming a verdict without spelling structural paths.
 */
class DistillVerdictStep
{
    public function __invoke(WorkflowState $state): WorkflowState
    {
        $verdict = $state->output('verdict');

        return $state
            ->set('consensus_reached', $verdict?->get('satisfied'))
            ->set('recommendation', $verdict?->get('judge.recommendation'))
            ->set('rationale', $verdict?->get('judge.rationale'))
            ->set('rounds_argued', $verdict?->get('iteration'))
            ->set('statements_heard', Transcript::in($state, 'verdict')->count());
    }
}
