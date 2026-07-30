<?php

namespace App\Agents;

use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

#[UseCheapestModel]
class RiskSkepticAgent implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You argue the strongest case AGAINST signing the deal under discussion. '
            .'Attack specific clauses, quantify downside where possible, and state exactly what would change your mind.';
    }
}
