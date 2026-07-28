<?php

namespace App\Support;

use App\Agents\EscalationAgent;
use App\Agents\ExtractClausesAgent;
use App\Agents\GenerateSummaryAgent;
use App\Agents\RiskAnalysisAgent;
use Illuminate\Support\Str;

/**
 * Deterministic, closure-based agent fakes so the demo (dashboard, queue
 * worker, and seeder) runs without a provider key. Enabled with
 * DEMO_FAKE_AGENTS=true; closures never exhaust, so a long-lived worker can
 * serve any number of runs.
 *
 * The risk score is derived from the prompt text flowing through the real
 * workflow: contracts whose extracted clauses mention unlimited liability
 * escalate, everything else auto-approves — both branches stay reachable.
 */
class FakeAgents
{
    public static function register(): void
    {
        ExtractClausesAgent::fake(function (string $prompt) {
            $risky = Str::contains($prompt, 'unlimited', ignoreCase: true);

            return implode("\n", array_filter([
                '1. Fees — $200,000 per year, auto-renewing unless 90 days notice is given.',
                $risky ? '2. Liability — the Supplier\'s liability is UNLIMITED.' : '2. Liability — capped at 12 months of fees.',
                $risky ? '3. IP — all work product is assigned to the Supplier.' : '3. IP — all work product is assigned to the Client.',
                '4. Termination — for convenience with '.($risky ? '5 days' : '60 days').' notice.',
            ]));
        });

        RiskAnalysisAgent::fake(function (string $prompt) {
            $risky = Str::contains($prompt, 'unlimited', ignoreCase: true);

            return $risky
                ? ['riskScore' => 9, 'rationale' => 'Unlimited liability, supplier-owned IP, and a 5-day termination window put nearly all commercial risk on the Client.']
                : ['riskScore' => 3, 'rationale' => 'Liability is capped, IP is assigned to the Client, and termination terms are standard.'];
        });

        EscalationAgent::fake(fn (string $prompt) => 'ESCALATION — legal review required. '
            .'This agreement scores 9/10 on risk: unlimited supplier liability exposure, IP assignment away from the Client, '
            .'and an asymmetric 5-day termination-for-convenience clause. Recommend renegotiating clauses 2, 3 and 5 before signature.');

        GenerateSummaryAgent::fake(fn (string $prompt) => Str::contains($prompt, 'approved')
            ? 'The contract review is complete and the agreement was approved for signature. Key commercial terms were extracted, risk-scored, and cleared by the reviewer; the notes have been attached to the file.'
            : 'The contract review is complete and the agreement was REJECTED. The reviewer declined sign-off based on the flagged risk profile; the counterparty should be re-engaged with revised terms.');
    }
}
