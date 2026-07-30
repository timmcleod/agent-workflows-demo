<?php

namespace App\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

#[UseCheapestModel]
class DebateJudgeAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You judge a structured debate between a deal advocate and a risk skeptic. '
            .'Rule only on the arguments in the transcript. Declare consensus when the positions have genuinely converged.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'consensus' => $schema->boolean()->description('Have the debaters converged on a position?')->required(),
            'recommendation' => $schema->string()->description('The panel\'s current recommendation, one sentence.')->required(),
            'rationale' => $schema->string()->description('Why, in one short paragraph.')->required(),
        ];
    }
}
