<?php

namespace App\Execution;

use App\Models\JobEventModel;

final class JobEventReader
{
    /** @return list<array<string, mixed>> */
    public function forJob(string $jobId): array
    {
        return JobEventModel::query()
            ->where('job_id', $jobId)
            ->latest('id')
            ->limit(250)
            ->get()
            ->reverse()
            ->map(fn (JobEventModel $event): array => [
                'id' => (int) $event->id,
                'type' => (string) $event->event_type,
                'stage' => $event->stage !== null ? (string) $event->stage : null,
                'status' => $event->status !== null ? (string) $event->status : null,
                'message' => $event->message !== null ? (string) $event->message : null,
                'payload' => is_array($event->payload) ? $event->payload : [],
                'created_at' => $event->created_at?->toISOString(),
            ])
            ->values()
            ->all();
    }
}
