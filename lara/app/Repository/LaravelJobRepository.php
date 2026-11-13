<?php

namespace App\Repository;

use App\Models\JobModel;
use Core\Execution\JobRepository;
use Core\Job\Job;
use Core\Job\JobStatus;
use Core\ValueObject\JobId;
use Core\Pipeline\PipelineFactory;
use App\Pipeline\PayloadAwarePipeline;
use Illuminate\Database\QueryException;
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
            $this->attributes($job)
        );
    }

    public function saveIfMissing(Job $job): bool
    {
        try {
            return JobModel::firstOrCreate(
                ['id' => $job->id()->value()],
                $this->attributes($job)
            )->wasRecentlyCreated;
        } catch (QueryException $exception) {
            if ($this->exists($job->id())) {
                return false;
            }

            throw $exception;
        }
    }

    public function get(JobId $id): Job
    {
        $model = JobModel::find($id->value());

        if (! $model) {
            throw new RuntimeException("Job not found: {$id->value()}");
        }

        $pipeline = $this->pipelines->make($model->pipeline_name);

        $payload = $model->payload === null
            ? []
            : json_decode($model->payload, true, 512, JSON_THROW_ON_ERROR);

        if ($pipeline instanceof PayloadAwarePipeline) {
            $pipeline = $pipeline->withPayload(
                array_merge($payload, ['execution_job_id' => $model->id])
            );
        }

        return new Job(
            new JobId($model->id),
            $pipeline,
            payload: $payload
        );
    }

    public function exists(JobId $id): bool
    {
        return JobModel::whereKey($id->value())->exists();
    }

    public function updateStatus(JobId $id, JobStatus $status, ?string $error = null): void
    {
        $values = [
            'status' => $status->value,
            'updated_at' => now(),
            'error' => $error,
        ];

        if ($status === JobStatus::Running) {
            $values['attempts'] = JobModel::whereKey($id->value())->value('attempts') + 1;
            $values['started_at'] = now();
        }

        if (in_array($status, [JobStatus::Completed, JobStatus::Failed], true)) {
            $values['finished_at'] = now();
        }

        $updated = JobModel::whereKey($id->value())->update($values);

        if ($updated === 0 && ! $this->exists($id)) {
            throw new RuntimeException("Job not found: {$id->value()}");
        }
    }

    public function status(JobId $id): ?JobStatus
    {
        $status = JobModel::whereKey($id->value())->value('status');

        return $status === null ? null : JobStatus::from($status);
    }

    public function cancelIfPending(JobId $id, ?string $reason = null): bool
    {
        return JobModel::whereKey($id->value())
            ->where('status', JobStatus::Pending->value)
            ->update([
                'status' => JobStatus::Cancelled->value,
                'error' => $reason,
                'updated_at' => now(),
                'finished_at' => now(),
            ]) === 1;
    }

    public function requestCancellation(JobId $id, ?string $reason = null): bool
    {
        $model = JobModel::whereKey($id->value())
            ->whereIn('status', [JobStatus::Pending->value, JobStatus::Running->value])
            ->first();
        if ($model === null) {
            return false;
        }

        $payload = $model->payload === null
            ? []
            : json_decode((string) $model->payload, true, 512, JSON_THROW_ON_ERROR);
        $payload['cancellation_requested'] = true;
        $payload['cancellation_reason'] = $reason;

        return $model->update([
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function attributes(Job $job): array
    {
        return [
            'pipeline_name' => $job->pipeline()->name(),
            'status' => 'pending',
            'queue' => $job->queue(),
            'payload' => json_encode($job->payload(), JSON_THROW_ON_ERROR),
            'attempts' => 0,
            'error' => null,
            'started_at' => null,
            'finished_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
