<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PipelineRunController;
use App\Http\Middleware\ApiKeyAuth;
use App\Http\Middleware\IdempotencyKey;

Route::get('/', function () {
    return view('welcome');
});

$rateLimit = config('jobs.http.rate_limit', '60,1');

Route::middleware([ApiKeyAuth::class, IdempotencyKey::class, "throttle:{$rateLimit}"])->group(function () {
    Route::post('/pipelines/{name}/run', [PipelineRunController::class, 'run']);
    Route::post('/api/v1/pipelines/{name}/run', [PipelineRunController::class, 'run']);
});
