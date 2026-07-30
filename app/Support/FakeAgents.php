<?php

namespace App\Support;

use App\Agents\DealAdvocateAgent;
use App\Agents\DebateJudgeAgent;
use App\Agents\EscalationAgent;
use App\Agents\ExtractClausesAgent;
use App\Agents\GenerateSummaryAgent;
use App\Agents\RiskAnalysisAgent;
use App\Agents\RiskSkepticAgent;
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

        static::registerDebate();
    }

    /**
     * A scripted three-round debate arc for the contract-debate workflow.
     * Debaters infer their round from the prompt: an opening protocol means
     * round 1; a "(round 2)" transcript marker means they are speaking in
     * round 3; otherwise round 2. The judge concedes consensus once the
     * transcript contains round-3 statements.
     */
    protected static function registerDebate(): void
    {
        DealAdvocateAgent::fake(function (string $prompt) {
            return match (true) {
                Str::contains($prompt, 'opening position') => 'Sign it. A $200k committed annual spend locks in a supplier the delivery team already rates, '
                    .'the auto-renewal gives us pricing stability, and every clause the lawyers dislike is standard opening-draft posturing we can negotiate away at renewal.',
                Str::contains($prompt, '(round 2)') => 'Agreed on both amendments: liability capped at 12 months of fees AND work product assigned to the Client. '
                    .'With those two changes I recommend signing — the commercials are too good to lose over drafting.',
                default => 'The skeptic is right about the liability clause — unlimited exposure is not defensible, and I concede it must be capped at 12 months of fees. '
                    .'But that is one amendment, not a reason to walk: the fee, the renewal terms, and the delivery record all still argue for this deal.',
            };
        });

        // The skeptic speaks second, so by the time it is prompted in round
        // N the advocate's "(round N)" entry is already in the transcript —
        // its round markers sit one ahead of the advocate's.
        RiskSkepticAgent::fake(function (string $prompt) {
            return match (true) {
                Str::contains($prompt, 'opening position') => 'Do not sign as drafted. Unlimited supplier liability, all IP assigned to the Supplier, and a 5-day '
                    .'termination-for-convenience window put essentially every commercial risk on the Client. Any one of these is a walk-away clause; this draft has three.',
                Str::contains($prompt, '(round 3)') => 'With the liability cap and the IP reassignment both agreed, my objections are resolved. '
                    .'I join the recommendation: sign contingent on those two amendments.',
                default => 'The advocate concedes the liability cap — good, that was the largest exposure. But the IP assignment clause alone transfers everything '
                    .'we build to the Supplier. Reassign work product to the Client and I will drop my opposition; without it, still a no.',
            };
        });

        DebateJudgeAgent::fake(function (string $prompt) {
            return Str::contains($prompt, '(round 3)')
                ? [
                    'consensus' => true,
                    'recommendation' => 'Sign, contingent on amending clause 2 (cap liability at 12 months of fees) and clause 3 (assign work product to the Client).',
                    'rationale' => 'Both debaters now endorse the same contingent position: the advocate accepted both amendments and the skeptic withdrew opposition on that basis. The remaining terms were not in dispute.',
                ]
                : [
                    'consensus' => false,
                    'recommendation' => 'Do not sign yet; the panel is still apart on liability and IP terms.',
                    'rationale' => 'The advocate argues commercial upside while the skeptic maintains specific clause objections that have not yet been conceded or rebutted in full.',
                ];
        });
    }
}
