<?php

namespace App\AgentWorkflows;

use App\Agents\EscalationAgent;
use App\Agents\ExtractClausesAgent;
use App\Agents\GenerateSummaryAgent;
use App\Agents\RiskAnalysisAgent;
use App\AgentWorkflows\Steps\AutoApproveStep;
use App\AgentWorkflows\Steps\EnrichmentStep;
use TimMcLeod\AgentWorkflows\Workflow;
use TimMcLeod\AgentWorkflows\WorkflowDefinition;
use TimMcLeod\AgentWorkflows\WorkflowState;

class ContractReview extends Workflow
{
    public function build(WorkflowDefinition $workflow): WorkflowDefinition
    {
        return $workflow
            ->step(ExtractClausesAgent::class, prompt: $this->extractPrompt(...))
            ->step(RiskAnalysisAgent::class, prompt: $this->riskPrompt(...))
            ->step(EnrichmentStep::class)
            ->when($this->isHighRisk(...),
                then: EscalationAgent::class,
                else: AutoApproveStep::class,
                thenPrompt: $this->escalationPrompt(...))
            ->awaitHuman(reason: 'Final sign-off required', schema: [
                'approved' => 'required|boolean',
                'notes' => 'nullable|string',
            ])
            ->step(GenerateSummaryAgent::class, prompt: $this->summaryPrompt(...));
    }

    protected function extractPrompt(WorkflowState $state): string
    {
        return "Extract the key clauses from this contract:\n\n".$state->get('contract');
    }

    protected function riskPrompt(WorkflowState $state): string
    {
        return "Assess the risk of a contract with these clauses:\n\n"
            .$state->get('steps.ExtractClausesAgent.text');
    }

    protected function isHighRisk(WorkflowState $state): bool
    {
        return (int) $state->get('steps.RiskAnalysisAgent.structured.riskScore', 0) > 7;
    }

    protected function escalationPrompt(WorkflowState $state): string
    {
        return sprintf(
            "Write an escalation note. Risk score: %d/10.\nRationale: %s",
            $state->get('steps.RiskAnalysisAgent.structured.riskScore'),
            $state->get('steps.RiskAnalysisAgent.structured.rationale'),
        );
    }

    protected function summaryPrompt(WorkflowState $state): string
    {
        return sprintf(
            "Summarize this contract review in a short paragraph.\nRisk score: %d/10.\nSign-off: %s.\nReviewer notes: %s",
            $state->get('steps.RiskAnalysisAgent.structured.riskScore'),
            $state->get('approved') ? 'approved' : 'rejected',
            $state->get('notes') ?? 'none',
        );
    }
}
