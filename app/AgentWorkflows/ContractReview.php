<?php

namespace App\AgentWorkflows;

use App\Agents\EscalationAgent;
use App\Agents\ExtractClausesAgent;
use App\Agents\GenerateSummaryAgent;
use App\Agents\RiskAnalysisAgent;
use App\AgentWorkflows\Steps\AutoApproveStep;
use App\AgentWorkflows\Steps\EnrichmentStep;
use Carbon\CarbonInterval;
use TimMcLeod\AgentWorkflows\Workflow;
use TimMcLeod\AgentWorkflows\WorkflowDefinition;

class ContractReview extends Workflow
{
    public function stateClass(): string
    {
        return ContractReviewState::class;
    }

    public function build(WorkflowDefinition $workflow): WorkflowDefinition
    {
        return $workflow
            ->step(ExtractClausesAgent::class, prompt: $this->extractPrompt(...))
            ->step(RiskAnalysisAgent::class, prompt: $this->riskPrompt(...))
            ->step(EnrichmentStep::class)
            ->when(fn (ContractReviewState $state) => $state->isHighRisk(),
                then: EscalationAgent::class,
                else: AutoApproveStep::class,
                thenPrompt: $this->escalationPrompt(...))
            ->awaitHuman(reason: 'Final sign-off required', schema: [
                'approved' => 'required|boolean',
                'notes' => 'nullable|string',
            ], timeout: CarbonInterval::days(3), timeoutResponse: [
                'approved' => false,
                'notes' => 'Auto-rejected: sign-off timed out after 3 days.',
            ])
            ->step(GenerateSummaryAgent::class, prompt: $this->summaryPrompt(...));
    }

    protected function extractPrompt(ContractReviewState $state): string
    {
        return "Extract the key clauses from this contract:\n\n".$state->contract();
    }

    protected function riskPrompt(ContractReviewState $state): string
    {
        return "Assess the risk of a contract with these clauses:\n\n".$state->clauses();
    }

    protected function escalationPrompt(ContractReviewState $state): string
    {
        return sprintf(
            "Write an escalation note. Risk score: %d/10.\nRationale: %s",
            $state->riskScore(),
            $state->riskRationale(),
        );
    }

    protected function summaryPrompt(ContractReviewState $state): string
    {
        return sprintf(
            "Summarize this contract review in a short paragraph.\nRisk score: %d/10.\nSign-off: %s.\nReviewer notes: %s",
            $state->riskScore(),
            $state->approved() ? 'approved' : 'rejected',
            $state->reviewerNotes(),
        );
    }
}
