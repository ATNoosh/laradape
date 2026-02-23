<?php

namespace App\Http\Controllers;

use App\Http\Requests\RunPipelineRequest;
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
            return response()->json([
                'error' => $e->getMessage(),
            ], 404);
        }

        $job = new Job(
            new JobId($request->jobId()),
            $pipeline
        );

        $report = $dispatcher->dispatch($job);

        $status = $report->isSkipped()
            ? 'skipped'
            : $report->result()->state();

        if ($report->isSkipped()) {
            return response()->json([
                'job_id' => $job->id()->value(),
                'status' => $status,
            ], 409);
        }

        if (! $report->isSuccessful()) {
            return response()->json([
                'job_id' => $job->id()->value(),
                'status' => $status,
            ], 409);
        }

        return response()->json([
            'job_id' => $job->id()->value(),
            'status' => $status,
        ]);
    }
}
