<?php

namespace App\Providers;

use App\Support\FakeAgents;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Lets the whole demo (dashboard, worker, seeder) run without a
        // provider key. Remove DEMO_FAKE_AGENTS from .env to use real agents.
        if (env('DEMO_FAKE_AGENTS', false)) {
            FakeAgents::register();
        }
    }
}
