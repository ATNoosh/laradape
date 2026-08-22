<?php

namespace App\Http\Controllers;

use App\Execution\QueuedJobSubmission;
use App\Http\Requests\RunPipelineRequest;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\ErrorResponse;
use App\Http\Responses\JobResponse;
use Core\Execution\JobDispatcherInterface;
use Core\Job\Job;
use Core\Pipeline\PipelineFactory;
use Core\ValueObject\JobId;
use InvalidArgumentException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PipelineRunController extends Controller
{
    public function queue(
        string $name,
        Request $httpRequest,
        RunPipelineRequest $request,
        PipelineFactory $pipelines,
        QueuedJobSubmission $submission
    ): JsonResponse {
        try {
            $pipeline = $pipelines->make($name);
        } catch (InvalidArgumentException $e) {
            $response = response()->json(ApiResponse::error(
                new ErrorResponse(
                    code: 'pipeline_not_found',
                    message: $e->getMessage()
                )
            ), 404);
            return $this->withDeprecationHeaders($httpRequest, $response);
        }

        $jobId = new JobId($request->jobId());
        $submission->submit(new Job($jobId, $pipeline));
        $response = response()->json(ApiResponse::success(
            new JobResponse($jobId->value(), 'queued')
        ), 202);

        return $this->withDeprecationHeaders($httpRequest, $response);
    }

    public function run(
        string $name,
        Request $httpRequest,
        RunPipelineRequest $request,
        PipelineFactory $pipelines,
        JobDispatcherInterface $dispatcher
    ): JsonResponse {
        try {
            $pipeline = $pipelines->make($name);
        } catch (InvalidArgumentException $e) {
            $response = response()->json(ApiResponse::error(
                new ErrorResponse(
                    code: 'pipeline_not_found',
                    message: $e->getMessage()
                )
            ), 404);
            return $this->withDeprecationHeaders($httpRequest, $response);
        }

        $job = new Job(
            new JobId($request->jobId()),
            $pipeline
        );

        $report = $dispatcher->dispatch($job);

        $payload = JobResponse::fromReport($report);

        if ($report->isSkipped() || ! $report->isSuccessful()) {
            $details = $payload->jsonSerialize();
            $response = response()->json(ApiResponse::error(
                new ErrorResponse(
                    code: 'job_conflict',
                    message: (string) ($details['message'] ?? 'Job conflict'),
                    details: $details
                )
            ), 409);
            return $this->withDeprecationHeaders($httpRequest, $response);
        }

        $response = response()->json(ApiResponse::success($payload));
        return $this->withDeprecationHeaders($httpRequest, $response);
    }

    private function withDeprecationHeaders(Request $request, JsonResponse $response): JsonResponse
    {
        if (str_starts_with($request->path(), 'pipelines/')) {
            $response->headers->set('Deprecation', 'true');
            $response->headers->set('Sunset', '2026-12-31');
            $response->headers->set(
                'Link',
                '</api/v1/pipelines/{name}/run>; rel="successor-version"'
            );
        }

        return $response;
    }
}
