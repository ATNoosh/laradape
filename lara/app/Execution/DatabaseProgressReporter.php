<?php

namespace App\Execution;

use App\Models\JobEventModel;
use App\Models\JobModel;
use Pupgrader\Reporting\FileChange;
use Pupgrader\Reporting\ProgressReporter;

final class DatabaseProgressReporter implements ProgressReporter
{
    public function stepStarted(string $jobId, string $stage, array $data = []): void
    {
        $this->event($jobId, 'stage.started', $stage, 'running', $data);
        $values = ['current_stage' => $stage, 'last_message' => "Started {$stage}"];
        if (isset($data['hop'])) {
            $values['current_hop'] = (string) $data['hop'];
        }
        $this->update($jobId, $values);
    }

    public function stepCompleted(string $jobId, string $stage, array $data = []): void
    {
        $this->event($jobId, 'stage.completed', $stage, 'completed', $data);
        $values = [
            'current_stage' => $stage,
            'last_message' => "Completed {$stage}",
            'progress_percent' => $stage === 'BuildUpgradeTaskQueueStep'
                ? 100
                : min(95, max(5, JobEventModel::where('job_id', $jobId)->where('event_type', 'stage.completed')->count() * 5)),
        ];
        if (isset($data['hop'])) {
            $values['current_hop'] = (string) $data['hop'];
        }
        if (isset($data['file_count'])) {
            $values['files_total'] = (int) $data['file_count'];
        }
        if (isset($data['files_changed'])) {
            $values['files_changed'] = (int) $data['files_changed'];
        }
        if (isset($data['review_candidates'])) {
            $values['files_review'] = (int) $data['review_candidates'];
        }
        $this->update($jobId, $values);
    }

    public function stepFailed(string $jobId, string $stage, string $message): void
    {
        $this->event($jobId, 'stage.failed', $stage, 'failed', [], $message);
        $this->update($jobId, [
            'current_stage' => $stage,
            'last_message' => $message,
        ]);
    }

    public function fileChanged(string $jobId, FileChange $change): void
    {
        $type = is_file($change->file) ? 'file.changed' : 'path.processed';
        $file = $this->relativePath($jobId, $change->file);
        $this->event($jobId, $type, 'file-change', 'changed', [
            'file' => $file,
            'rule' => $change->rule,
            'hop' => $change->hop,
            'description' => $change->description,
        ], $change->description);

        if ($type === 'file.changed') {
            $files = JobEventModel::query()
                ->where('job_id', $jobId)
                ->where('event_type', 'file.changed')
                ->get(['payload'])
                ->map(function (JobEventModel $event): ?string {
                    $payload = is_array($event->payload)
                        ? $event->payload
                        : json_decode((string) $event->getRawOriginal('payload'), true);

                    return is_array($payload) ? ($payload['file'] ?? null) : null;
                })
                ->filter()
                ->unique()
                ->count();
            $this->update($jobId, ['files_changed' => $files]);
        }
    }

    /** @param array<string, mixed> $payload */
    private function event(
        string $jobId,
        string $type,
        string $stage,
        string $status,
        array $payload = [],
        ?string $message = null
    ): void {
        if ($jobId === '') {
            return;
        }
        JobEventModel::create([
            'job_id' => $jobId,
            'event_type' => $type,
            'stage' => $stage,
            'status' => $status,
            'message' => $message,
            'payload' => $payload,
        ]);
    }

    /** @param array<string, mixed> $values */
    private function update(string $jobId, array $values): void
    {
        if ($jobId !== '') {
            JobModel::whereKey($jobId)->update(array_merge($values, ['updated_at' => now()]));
        }
    }

    private function relativePath(string $jobId, string $path): string
    {
        $payload = JobModel::whereKey($jobId)->value('payload');
        $data = is_string($payload) ? json_decode($payload, true) : [];
        $root = is_array($data) ? (string) ($data['project_path'] ?? '') : '';
        $normalizedPath = str_replace('\\', '/', $path);
        $normalizedRoot = rtrim(str_replace('\\', '/', $root), '/');

        if ($normalizedRoot !== '' && preg_match(
            '#^'.preg_quote($normalizedRoot, '#').'/#i',
            $normalizedPath
        ) === 1) {
            return ltrim(substr($normalizedPath, strlen($normalizedRoot)), '/');
        }

        return $normalizedPath;
    }
}
