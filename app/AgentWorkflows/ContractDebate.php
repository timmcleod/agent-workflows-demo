<?php

namespace App\AgentWorkflows;

use App\Agents\DealAdvocateAgent;
use App\Agents\DebateJudgeAgent;
use App\Agents\RiskSkepticAgent;
use App\AgentWorkflows\Steps\DistillVerdictStep;
use TimMcLeod\AgentWorkflows\Workflow;
use TimMcLeod\AgentWorkflows\WorkflowDefinition;
use TimMcLeod\AgentWorkflows\WorkflowState;

/**
 * The debate() pattern: an advocate and a skeptic argue whether to sign the
 * contract, a judge rules on the transcript after every round, and the loop
 * stops on consensus (or at the 4-round cap). Every round is a checkpoint —
 * watch them land one by one on the dashboard.
 */
class ContractDebate extends Workflow
{
    public function build(WorkflowDefinition $workflow): WorkflowDefinition
    {
        return $workflow
            ->debate(
                ['advocate' => DealAdvocateAgent::class, 'skeptic' => RiskSkepticAgent::class],
                judge: DebateJudgeAgent::class,
                as: 'verdict',
                rounds: 4,
                topic: fn (WorkflowState $s) => "Should the Client sign this agreement as drafted?\n\n".$s->get('contract'),
            )
            ->step(DistillVerdictStep::class);
    }
}
