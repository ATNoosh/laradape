<?php

namespace App\Execution;

use App\Models\JobModel;

final class JobChainDetailsReader
{
    public function __construct(
        private JobDetailsReader $details
    ) {}

    public function find(string $jobId): ?JobChainDetails
    {
        $root = JobModel::find($jobId);
        if ($root === null || $root->pipeline_name !== 'pupgrader.upgrade.hop') {
            return null;
        }

        $rootPayload = $this->payload($root->payload);
        $rootJobId = (string) ($rootPayload['root_job_id'] ?? $root->id);
        $jobIds = [];
        $hopIndexes = [];

        foreach (JobModel::query()
            ->where('pipeline_name', 'pupgrader.upgrade.hop')
            ->get(['id', 'payload']) as $model) {
            $payload = $this->payload($model->payload);
            if ((string) ($payload['root_job_id'] ?? $model->id) !== $rootJobId) {
                continue;
            }

            $id = (string) $model->id;
            $jobIds[] = $id;
            $hopIndexes[$id] = (int) ($payload['hop_index'] ?? PHP_INT_MAX);
        }

        usort($jobIds, fn (string $left, string $right): int => $hopIndexes[$left] <=> $hopIndexes[$right]);
        $jobs = array_values(array_filter(array_map(
            fn (string $id): ?JobDetails => $this->details->find($id),
            $jobIds
        )));
        $total = max((int) ($rootPayload['hop_count'] ?? 0), count($jobs));
        $completed = count(array_filter($jobs, fn (JobDetails $job): bool => $job->status === 'completed'));
        $failed = count(array_filter($jobs, fn (JobDetails $job): bool => $job->status === 'failed'));
        $cancelled = count(array_filter($jobs, fn (JobDetails $job): bool => $job->status === 'cancelled'));
        $running = count(array_filter($jobs, fn (JobDetails $job): bool => $job->status === 'running'));
        $cancellationRequested = count(array_filter(
            $jobs,
            fn (JobDetails $job): bool => $job->cancellationRequested
        )) > 0;
        $status = $failed > 0
            ? 'failed'
            : ($running > 0 ? 'running' : ($cancelled > 0 || $cancellationRequested
                ? 'cancelled'
                : ($completed === $total ? 'completed' : 'pending')));
        $current = array_values(array_filter(
            $jobs,
            fn (JobDetails $job): bool => $job->status !== 'completed' || $job->cancellationRequested
        ));

        return new JobChainDetails(
            rootJobId: $rootJobId,
            status: $status,
            totalHops: $total,
            completedHops: $completed,
            failedHops: $failed,
            cancelledHops: $cancelled,
            currentHop: $current === [] ? null : $current[0]->hopIndex,
            jobs: $jobs,
        );
    }

    /** @return array<string, mixed> */
    private function payload(mixed $payload): array
    {
        return $payload === null
            ? []
            : json_decode((string) $payload, true, 512, JSON_THROW_ON_ERROR);
    }
}
