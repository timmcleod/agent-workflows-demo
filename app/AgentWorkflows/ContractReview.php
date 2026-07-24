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
            ->step(ExtractClausesAgent::class,
                prompt: fn (WorkflowState $s) => "Extract the key clauses from this contract:\n\n".$s->get('contract'))
            ->step(RiskAnalysisAgent::class,
                prompt: fn (WorkflowState $s) => "Assess the risk of a contract with these clauses:\n\n"
                    .$s->get('steps.ExtractClausesAgent.text'))
            ->step(EnrichmentStep::class)
            ->when(fn (WorkflowState $s) => (int) $s->get('steps.RiskAnalysisAgent.structured.riskScore', 0) > 7,
                then: EscalationAgent::class,
                else: AutoApproveStep::class,
                thenPrompt: fn (WorkflowState $s) => sprintf(
                    "Write an escalation note. Risk score: %d/10.\nRationale: %s",
                    $s->get('steps.RiskAnalysisAgent.structured.riskScore'),
                    $s->get('steps.RiskAnalysisAgent.structured.rationale'),
                ))
            ->awaitHuman(reason: 'Final sign-off required', schema: [
                'approved' => 'required|boolean',
                'notes' => 'nullable|string',
            ])
            ->step(GenerateSummaryAgent::class,
                prompt: fn (WorkflowState $s) => sprintf(
                    "Summarize this contract review in a short paragraph.\nRisk score: %d/10.\nSign-off: %s.\nReviewer notes: %s",
                    $s->get('steps.RiskAnalysisAgent.structured.riskScore'),
                    $s->get('approved') ? 'approved' : 'rejected',
                    $s->get('notes') ?? 'none',
                ));
    }
}
