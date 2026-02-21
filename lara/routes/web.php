<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PipelineRunController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/pipelines/{name}/run', [PipelineRunController::class, 'run']);
