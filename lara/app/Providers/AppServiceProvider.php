<?php

namespace App\Providers;

use App\Execution\LaravelRetryScheduler;
use Core\Execution\RetryScheduler;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            RetryScheduler::class,
            LaravelRetryScheduler::class
        );    
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
