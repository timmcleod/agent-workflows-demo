<?php

namespace App\Agents;

use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

#[UseCheapestModel]
class DealAdvocateAgent implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You argue the strongest commercial case FOR signing the deal under discussion. '
            .'Be concrete about upside, concede weak points honestly, and update your position when rebutted.';
    }
}
