<?php

namespace App\Repository;

use App\Models\JobModel;
use Core\Job\Job;
use Core\Repository\JobRepository;
use Core\ValueObject\JobId;
use Core\Pipeline\PipelineFactory;
use RuntimeException;

namespace App\Repository;

use App\Models\JobModel;
use Core\Job\Job;
use Core\Repository\JobRepository;
use Core\ValueObject\JobId;
use Core\Pipeline\PipelineFactory;
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
            ]
        );
    }

    public function get(JobId $id): Job
    {
        $model = JobModel::find($id->value());

        if (! $model) {
            throw new RuntimeException("Job not found: {$id->value()}");
        }

        return new Job(
            new JobId($model->id),
            $this->pipelines->make($model->pipeline)
        );
    }

    public function exists(JobId $id): bool
    {
        return JobModel::whereKey($id->value())->exists();
    }
}
