<?php

namespace App\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;
use TimMcLeod\AgentWorkflows\Contracts\HasWorkflowPrompt;
use TimMcLeod\AgentWorkflows\WorkflowState;

#[UseCheapestModel]
class RiskAnalysisAgent implements Agent, HasStructuredOutput, HasWorkflowPrompt
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You assess the legal and commercial risk of contracts based on their extracted clauses.';
    }

    public function workflowPrompt(WorkflowState $state): string
    {
        return "Assess the risk of a contract with these clauses:\n\n"
            .$state->get('steps.ExtractClausesAgent.text');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'riskScore' => $schema->integer()->description('Overall risk from 1 (trivial) to 10 (severe).')->required(),
            'rationale' => $schema->string()->description('One-paragraph justification.')->required(),
        ];
    }
}
