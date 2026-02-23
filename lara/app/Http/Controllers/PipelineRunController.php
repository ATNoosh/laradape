<?php

namespace App\Http\Controllers;

use App\Http\Requests\RunPipelineRequest;
use App\Http\Responses\ErrorResponse;
use App\Http\Responses\JobResponse;
use Core\Execution\JobDispatcherInterface;
use Core\Job\Job;
use Core\Pipeline\PipelineFactory;
use Core\ValueObject\JobId;
use InvalidArgumentException;
use Illuminate\Http\JsonResponse;

final class PipelineRunController extends Controller
{
    public function run(
        string $name,
        RunPipelineRequest $request,
        PipelineFactory $pipelines,
        JobDispatcherInterface $dispatcher
    ): JsonResponse {
        try {
            $pipeline = $pipelines->make($name);
        } catch (InvalidArgumentException $e) {
            return response()->json(new ErrorResponse(
                code: 'pipeline_not_found',
                message: $e->getMessage()
            ), 404);
        }

        $job = new Job(
            new JobId($request->jobId()),
            $pipeline
        );

        $report = $dispatcher->dispatch($job);

        $payload = JobResponse::fromReport($report);

        if ($report->isSkipped() || ! $report->isSuccessful()) {
            $details = $payload->jsonSerialize();
            return response()->json(new ErrorResponse(
                code: 'job_conflict',
                message: (string) ($details['message'] ?? 'Job conflict'),
                details: $details
            ), 409);
        }

        return response()->json($payload);
    }
}
