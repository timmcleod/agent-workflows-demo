<?php

namespace App\AgentWorkflows;

use App\Agents\ExtractClausesAgent;
use App\Agents\RiskAnalysisAgent;
use TimMcLeod\AgentWorkflows\WorkflowState;

/**
 * Typed lens over the contract-review run's state bag: every step, prompt,
 * and condition receives this class, so the bag's structure lives here and
 * nowhere else.
 */
class ContractReviewState extends WorkflowState
{
    public function contract(): string
    {
        return (string) $this->get('contract');
    }

    public function clauses(): ?string
    {
        return $this->output(ExtractClausesAgent::class)?->text();
    }

    public function riskScore(): int
    {
        return (int) $this->output(RiskAnalysisAgent::class)?->structured('riskScore', 0);
    }

    public function riskRationale(): ?string
    {
        return $this->output(RiskAnalysisAgent::class)?->structured('rationale');
    }

    public function isHighRisk(): bool
    {
        return $this->riskScore() > 7;
    }

    public function approved(): bool
    {
        return (bool) $this->get('approved');
    }

    public function reviewerNotes(): string
    {
        return $this->get('notes') ?? 'none';
    }

    public function shouldSimulateFailure(): bool
    {
        return (bool) $this->get('simulate_failure');
    }
}
