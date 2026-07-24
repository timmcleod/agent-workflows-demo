<?php

namespace App\Agents;

use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

#[UseCheapestModel]
class EscalationAgent implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You write short, factual escalation notes for the legal team about high-risk contracts.';
    }
}
