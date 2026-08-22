<?php

namespace App\Repository;

use App\Models\JobModel;
use Core\Execution\JobRepository;
use Core\Job\Job;
use Core\Job\JobStatus;
use Core\ValueObject\JobId;
use Core\Pipeline\PipelineFactory;
use App\Pipeline\PayloadAwarePipeline;
use RuntimeException;

final class LaravelJobRepository implements JobRepository
{
    public function __construct(
        private PipelineFactory $pipelines
    ) {}

    public function save(Job $job): void
    {
        JobModel::updateOrCreate(
            ['id' => $job->id()->value()],
            [
                'pipeline_name' => $job->pipeline()->name(),
                'status' => 'pending',
                'queue' => $job->queue(),
                'payload' => json_encode($job->payload(), JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function get(JobId $id): Job
    {
        $model = JobModel::find($id->value());

        if (! $model) {
            throw new RuntimeException("Job not found: {$id->value()}");
        }

        $pipeline = $this->pipelines->make($model->pipeline_name);

        if ($pipeline instanceof PayloadAwarePipeline) {
            $pipeline = $pipeline->withPayload(
                $model->payload === null
                    ? []
                    : json_decode($model->payload, true, 512, JSON_THROW_ON_ERROR)
            );
        }

        return new Job(
            new JobId($model->id),
            $pipeline,
            payload: $model->payload === null
                ? []
                : json_decode($model->payload, true, 512, JSON_THROW_ON_ERROR)
        );
    }

    public function exists(JobId $id): bool
    {
        return JobModel::whereKey($id->value())->exists();
    }

    public function updateStatus(JobId $id, JobStatus $status): void
    {
        $updated = JobModel::whereKey($id->value())->update([
            'status' => $status->value,
            'updated_at' => now(),
        ]);

        if ($updated === 0 && ! $this->exists($id)) {
            throw new RuntimeException("Job not found: {$id->value()}");
        }
    }

    public function status(JobId $id): ?JobStatus
    {
        $status = JobModel::whereKey($id->value())->value('status');

        return $status === null ? null : JobStatus::from($status);
    }
}
