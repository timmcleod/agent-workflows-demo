<?php

namespace App\Agents;

use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;
use TimMcLeod\AgentWorkflows\Contracts\HasWorkflowPrompt;
use TimMcLeod\AgentWorkflows\WorkflowState;

#[UseCheapestModel]
class GenerateSummaryAgent implements Agent, HasWorkflowPrompt
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You write the final review summary for a completed contract review.';
    }

    public function workflowPrompt(WorkflowState $state): string
    {
        return sprintf(
            "Summarize this contract review in a short paragraph.\nRisk score: %d/10.\nSign-off: %s.\nReviewer notes: %s",
            $state->get('steps.RiskAnalysisAgent.structured.riskScore'),
            $state->get('approved') ? 'approved' : 'rejected',
            $state->get('notes') ?? 'none',
        );
    }
}
