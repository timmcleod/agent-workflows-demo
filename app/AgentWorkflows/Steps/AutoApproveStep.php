<?php

namespace App\AgentWorkflows\Steps;

use TimMcLeod\AgentWorkflows\WorkflowState;

class AutoApproveStep
{
    public function __invoke(WorkflowState $state): WorkflowState
    {
        return $state->set('auto_cleared', true);
    }
}
