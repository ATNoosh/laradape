<?php

use App\Http\Controllers\JobStatusController;
use App\Http\Controllers\PipelineRunController;
use App\Http\Controllers\UpgradeQueueController;
use App\Http\Middleware\ApiKeyAuth;
use App\Http\Middleware\IdempotencyKey;
use Illuminate\Support\Facades\Route;

$rateLimit = config('jobs.http.rate_limit', '60,1');

Route::middleware([ApiKeyAuth::class, "throttle:{$rateLimit}", IdempotencyKey::class])
    ->prefix('v1')
    ->group(function (): void {
        Route::post('/pipelines/{name}/run', [PipelineRunController::class, 'run']);
        Route::post('/pipelines/{name}/queue', [PipelineRunController::class, 'queue']);
        Route::get('/jobs/{id}', [JobStatusController::class, 'show']);
        Route::get('/jobs/{id}/chain', [JobStatusController::class, 'chain']);
        Route::post('/jobs/{id}/cancel', [JobStatusController::class, 'cancel']);
        Route::post('/upgrades/queue', [UpgradeQueueController::class, 'store']);
});
