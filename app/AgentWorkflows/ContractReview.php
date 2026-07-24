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
            ->step(ExtractClausesAgent::class)
            ->step(RiskAnalysisAgent::class)
            ->step(EnrichmentStep::class)
            ->when(fn (WorkflowState $s) => (int) $s->get('steps.RiskAnalysisAgent.structured.riskScore', 0) > 7,
                then: EscalationAgent::class,
                else: AutoApproveStep::class)
            ->awaitHuman(reason: 'Final sign-off required', schema: [
                'approved' => 'required|boolean',
                'notes' => 'nullable|string',
            ])
            ->step(GenerateSummaryAgent::class);
    }
}
