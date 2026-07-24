<?php

namespace App\Agents;

use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;
use TimMcLeod\AgentWorkflows\Contracts\HasWorkflowPrompt;
use TimMcLeod\AgentWorkflows\WorkflowState;

#[UseCheapestModel]
class ExtractClausesAgent implements Agent, HasWorkflowPrompt
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You extract the key clauses from contracts. List each clause with a short title and a one-sentence summary.';
    }

    public function workflowPrompt(WorkflowState $state): string
    {
        return "Extract the key clauses from this contract:\n\n".$state->get('contract');
    }
}
