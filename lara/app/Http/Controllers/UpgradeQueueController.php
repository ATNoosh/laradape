<?php

namespace App\Http\Controllers;

use App\Execution\PupgraderHopScheduler;
use App\Http\Requests\QueueUpgradeRequest;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\JobResponse;
use Core\Pipeline\PipelineFactory;
use Core\ValueObject\JobId;
use Illuminate\Http\JsonResponse;

final class UpgradeQueueController extends Controller
{
    public function store(
        QueueUpgradeRequest $request,
        PipelineFactory $pipelines,
        PupgraderHopScheduler $scheduler
    ): JsonResponse {
        $jobId = new JobId($request->jobId());
        $scheduler->submitInitial($jobId, $request->payload());

        return response()->json(ApiResponse::success(
            new JobResponse($jobId->value(), 'queued')
        ), 202);
    }
}
