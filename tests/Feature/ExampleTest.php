<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Gate;
// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_redirects_to_the_workflow_dashboard(): void
    {
        $this->get('/')->assertRedirect('/agent-workflows');

        // Outside the local environment the dashboard requires the gate.
        $this->get('/agent-workflows')->assertForbidden();

        Gate::define('viewAgentWorkflows', fn ($user = null) => true);

        $this->get('/agent-workflows')->assertStatus(200);
    }
}
