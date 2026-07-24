<?php

namespace App\Agents;

use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

#[UseCheapestModel]
class GenerateSummaryAgent implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You write the final review summary for a completed contract review.';
    }
}
