<?php

namespace App\Execution;

final class JobDetails implements \JsonSerializable
{
    public function __construct(
        public readonly string $jobId,
        public readonly string $pipeline,
        public readonly string $queue,
        public readonly string $status,
        public readonly int $attempts,
        public readonly ?string $error,
        public readonly ?string $createdAt,
        public readonly ?string $startedAt,
        public readonly ?string $finishedAt,
        public readonly ?string $rootJobId = null,
        public readonly ?string $parentJobId = null,
        public readonly ?int $hopIndex = null,
        public readonly ?int $hopCount = null,
        public readonly bool $cancellationRequested = false,
        public readonly ?string $currentStage = null,
        public readonly ?string $currentHop = null,
        public readonly int $progressPercent = 0,
        public readonly int $filesTotal = 0,
        public readonly int $filesChanged = 0,
        public readonly int $filesReview = 0,
        public readonly ?string $lastMessage = null,
        public readonly bool $dryRun = false,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'job_id' => $this->jobId,
            'pipeline' => $this->pipeline,
            'queue' => $this->queue,
            'status' => $this->status,
            'attempts' => $this->attempts,
            'error' => $this->error,
            'created_at' => $this->createdAt,
            'started_at' => $this->startedAt,
            'finished_at' => $this->finishedAt,
            'current_stage' => $this->currentStage,
            'current_hop' => $this->currentHop,
            'progress_percent' => $this->progressPercent,
            'files_total' => $this->filesTotal,
            'files_changed' => $this->filesChanged,
            'files_review' => $this->filesReview,
            'last_message' => $this->lastMessage,
            'dry_run' => $this->dryRun,
            'hop_index' => $this->hopIndex,
            'hop_count' => $this->hopCount,
            'cancellation_requested' => $this->cancellationRequested,
            'chain' => $this->rootJobId === null ? null : [
                'root_job_id' => $this->rootJobId,
                'parent_job_id' => $this->parentJobId,
                'hop_index' => $this->hopIndex,
                'hop_count' => $this->hopCount,
                'cancellation_requested' => $this->cancellationRequested,
            ],
        ];
    }
}
