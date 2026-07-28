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
```

`.env.example` ships with `DEMO_FAKE_AGENTS=true`, so the whole demo runs with **no provider key** — the agents return deterministic faked responses. To use real agents instead, remove that line and add a key (the SDK defaults to OpenAI):

```bash
echo "OPENAI_API_KEY=sk-..." >> .env
# (or publish config/ai.php and switch the default provider to anthropic/gemini/etc.)
```

The queue uses the `database` driver so you can *see* the durability — jobs survive worker restarts.

## The dashboard

The demo installs [`timmcleod/agent-workflows-ui`](https://github.com/timmcleod/agent-workflows-ui), so every run below is also visible in the browser. Seed a few runs in interesting states and open it:

```bash
php artisan demo:seed
php artisan serve   # → http://localhost:8000/agent-workflows
```

You'll see each run rendered as a live flowchart — completed steps green, the taken branch highlighted, the untaken branch dimmed. Runs awaiting sign-off show an approval form generated from the step's schema; approve one, run `php artisan queue:work --stop-when-empty`, and watch the summary step light up.

![The dashboard showing a completed contract-review run: the escalation branch taken, auto-approve skipped, and the sign-off gate's interrupted-then-completed attempts in the audit trail](https://raw.githubusercontent.com/timmcleod/agent-workflows-ui/main/art/dashboard.png)

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
