<?php

namespace App\Execution;

use App\Models\JobModel;

final class JobDetailsReader
{
    public function find(string $jobId): ?JobDetails
    {
        $job = JobModel::find($jobId);

        if ($job === null) {
            return null;
        }

        $payload = $job->payload === null
            ? []
            : json_decode((string) $job->payload, true, 512, JSON_THROW_ON_ERROR);
        $isHop = $job->pipeline_name === 'pupgrader.upgrade.hop';

        return new JobDetails(
            jobId: (string) $job->id,
            pipeline: (string) $job->pipeline_name,
            queue: (string) $job->queue,
            status: (string) $job->status,
            attempts: (int) $job->attempts,
            error: $job->error !== null ? (string) $job->error : null,
            createdAt: $job->created_at?->toISOString(),
            startedAt: $job->started_at?->toISOString(),
            finishedAt: $job->finished_at?->toISOString(),
            rootJobId: $isHop ? (string) ($payload['root_job_id'] ?? $job->id) : null,
            parentJobId: $isHop && isset($payload['parent_job_id'])
                ? (string) $payload['parent_job_id']
                : null,
            hopIndex: $isHop && isset($payload['hop_index']) ? (int) $payload['hop_index'] : null,
            hopCount: $isHop && isset($payload['hop_count']) ? (int) $payload['hop_count'] : null,
            cancellationRequested: $isHop && (bool) ($payload['cancellation_requested'] ?? false),
            currentStage: $job->current_stage !== null ? (string) $job->current_stage : null,
            currentHop: $job->current_hop !== null ? (string) $job->current_hop : null,
            progressPercent: (int) $job->progress_percent,
            filesTotal: (int) $job->files_total,
            filesChanged: (int) $job->files_changed,
            filesReview: (int) $job->files_review,
            lastMessage: $job->last_message !== null ? (string) $job->last_message : null,
            dryRun: (bool) ($payload['dry_run'] ?? false),
        );
    }
}
