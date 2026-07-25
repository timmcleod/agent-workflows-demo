# Agent Workflows Demo

A runnable contract-review pipeline demonstrating [`timmcleod/agent-workflows`](https://github.com/timmcleod/agent-workflows): durable, resumable, human-interruptible agent workflows on the Laravel AI SDK.

The workflow (`app/AgentWorkflows/ContractReview.php`):

```
ExtractClausesAgent → RiskAnalysisAgent → EnrichmentStep
    → when(riskScore > 7) EscalationAgent | AutoApproveStep
    → awaitHuman(sign-off) → GenerateSummaryAgent
```

## Setup

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate

# The agents need a provider key. The SDK defaults to OpenAI:
echo "OPENAI_API_KEY=sk-..." >> .env
# (or publish config/ai.php and switch the default provider to anthropic/gemini/etc.)
```

The queue uses the `database` driver so you can *see* the durability — jobs survive worker restarts.

## 1. The happy path (with a human in the loop)

```bash
php artisan demo:run                       # starts the run; nothing executes yet
php artisan queue:work --stop-when-empty   # agent steps execute as queued jobs
php artisan demo:status <run-id>           # → awaiting_human: "Final sign-off required"
```

The run is now parked. Kill the worker, restart your machine, deploy — it waits.

```bash
php artisan demo:approve <run-id> --notes="LGTM"
php artisan queue:work --stop-when-empty   # summary agent runs
php artisan demo:status <run-id>           # → completed, full audit trail + token counts
```

## 2. The headline: fail at step 3, retry step 3

```bash
php artisan demo:run --fail                # enrichment will simulate an outage
php artisan queue:work --stop-when-empty
php artisan demo:status <run-id>           # → failed at EnrichmentStep
                                           #   (steps 1–2 completed & checkpointed)
php artisan demo:retry <run-id>            # heals the flag, retries from EnrichmentStep
php artisan queue:work --stop-when-empty
php artisan demo:status <run-id>
```

Check the attempts column in `demo:status`: the two agent steps ran **once**. Their tokens were paid for once. Only `EnrichmentStep` shows two attempts.

## Things to try

- Reject instead of approving: `php artisan demo:approve <run-id> --reject`.
- Break the workflow definition between run and resume to see strict definition-drift protection refuse to continue.
- Inspect the tables directly: `agent_workflow_runs`, `agent_workflow_steps`, `agent_workflow_interrupts`.
