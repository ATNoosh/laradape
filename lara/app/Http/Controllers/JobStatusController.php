<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\ErrorResponse;
use App\Execution\JobChainDetailsReader;
use App\Execution\JobChainCanceller;
use App\Execution\JobDetailsReader;
use Illuminate\Http\JsonResponse;

final class JobStatusController extends Controller
{
    public function show(string $id, JobDetailsReader $jobs): JsonResponse
    {
        $details = $jobs->find($id);

        if ($details === null) {
            return response()->json(ApiResponse::error(
                new ErrorResponse(
                    code: 'job_not_found',
                    message: "Job not found: {$id}"
                )
            ), 404);
        }

        return response()->json(ApiResponse::success($details));
    }

    public function chain(string $id, JobChainDetailsReader $chains): JsonResponse
    {
        $details = $chains->find($id);

        if ($details === null) {
            return response()->json(ApiResponse::error(
                new ErrorResponse(
                    code: 'job_chain_not_found',
                    message: "Job chain not found: {$id}"
                )
            ), 404);
        }

        return response()->json(ApiResponse::success($details));
    }

    public function cancel(
        string $id,
        JobChainDetailsReader $chains,
        JobChainCanceller $canceller
    ): JsonResponse {
        $chain = $chains->find($id);

        if ($chain === null) {
            return response()->json(ApiResponse::error(
                new ErrorResponse(
                    code: 'job_chain_not_found',
                    message: "Job chain not found: {$id}"
                )
            ), 404);
        }

        $canceller->cancel($chain);
        $updated = $chains->find($id);

        return response()->json(ApiResponse::success($updated), 202);
    }
}
