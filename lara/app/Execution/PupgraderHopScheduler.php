<?php

namespace App\Execution;

use Core\Execution\JobRepository;
use Core\Job\Job;
use Core\Pipeline\PipelineFactory;
use Core\ValueObject\JobId;
use Pupgrader\Version\PhpVersion;
use Pupgrader\Version\UpgradePath;

final class PupgraderHopScheduler
{
    public function __construct(
        private JobRepository $jobs,
        private PipelineFactory $pipelines,
        private QueuedJobSubmission $submission
    ) {}

    /** @param array<string, mixed> $payload */
    public function submitInitial(JobId $jobId, array $payload): JobId
    {
        $payload = $this->normalizePayload($payload, 0);
        $payload['root_job_id'] = $jobId->value();
        $payload['parent_job_id'] = null;

        return $this->submission->submit(new Job(
            $jobId,
            $this->pipelines->make('pupgrader.upgrade.hop'),
            payload: $payload
        ));
    }

    public function scheduleNext(JobId $completedJobId): ?JobId
    {
        $job = $this->jobs->get($completedJobId);
        if ($job->pipeline()->name() !== 'pupgrader.upgrade.hop') {
            return null;
        }

        $payload = $job->payload();
        if ((bool) ($payload['cancellation_requested'] ?? false)) {
            return null;
        }
        $current = (int) ($payload['hop_index'] ?? 0);
        $count = (int) ($payload['hop_count'] ?? 0);
        $next = $current + 1;

        if ($next >= $count) {
            return null;
        }

        $nextPayload = $this->normalizePayload($payload, $next);
        $nextPayload['root_job_id'] ??= $completedJobId->value();
        $nextPayload['parent_job_id'] = $completedJobId->value();
        $options = is_array($nextPayload['options'] ?? null) ? $nextPayload['options'] : [];
        if (! (bool) ($nextPayload['dry_run'] ?? false)
            && ($options['output_path'] ?? null) !== null
            && $options['output_path'] !== '') {
            $nextPayload['project_path'] = (string) $options['output_path'];
            $options['output_path'] = null;
            $nextPayload['options'] = $options;
        }

        $nextId = new JobId($completedJobId->value().':hop-'.$next);

        if ($this->jobs->exists($nextId)) {
            return $nextId;
        }

        return $this->submission->submitIfMissing(new Job(
            $nextId,
            $this->pipelines->make('pupgrader.upgrade.hop'),
            payload: $nextPayload
        ));
    }

    /** @param array<string, mixed> $payload */
    private function normalizePayload(array $payload, int $index): array
    {
        $hops = UpgradePath::between(
            PhpVersion::parse((string) ($payload['from'] ?? '')),
            PhpVersion::parse((string) ($payload['to'] ?? ''))
        );

        $payload['hop_index'] = $index;
        $payload['hop_count'] = count($hops);

        return $payload;
    }
}
