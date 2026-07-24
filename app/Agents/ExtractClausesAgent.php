<?php

namespace App\Agents;

use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

#[UseCheapestModel]
class ExtractClausesAgent implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You extract the key clauses from contracts. List each clause with a short title and a one-sentence summary.';
    }
}
