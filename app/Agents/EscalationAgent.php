<?php

namespace App\Agents;

use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;
use TimMcLeod\AgentWorkflows\Contracts\HasWorkflowPrompt;
use TimMcLeod\AgentWorkflows\WorkflowState;

#[UseCheapestModel]
class EscalationAgent implements Agent, HasWorkflowPrompt
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You write short, factual escalation notes for the legal team about high-risk contracts.';
    }

    public function workflowPrompt(WorkflowState $state): string
    {
        return sprintf(
            "Write an escalation note. Risk score: %d/10.\nRationale: %s",
            $state->get('steps.RiskAnalysisAgent.structured.riskScore'),
            $state->get('steps.RiskAnalysisAgent.structured.rationale'),
        );
    }
}
