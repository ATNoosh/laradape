<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\ErrorResponse;
use App\Http\Responses\JobStatusResponse;
use Core\Execution\JobRepository;
use Core\ValueObject\JobId;
use Illuminate\Http\JsonResponse;

final class JobStatusController extends Controller
{
    public function show(string $id, JobRepository $jobs): JsonResponse
    {
        $jobId = new JobId($id);
        $status = $jobs->status($jobId);

        if ($status === null) {
            return response()->json(ApiResponse::error(
                new ErrorResponse(
                    code: 'job_not_found',
                    message: "Job not found: {$id}"
                )
            ), 404);
        }

        return response()->json(ApiResponse::success(
            new JobStatusResponse($jobId, $status)
        ));
    }
}
