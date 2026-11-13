<?php

namespace App\Http\Controllers;

use App\Models\JobModel;
use Inertia\Inertia;
use Inertia\Response;
use App\Profiles\ProfileCatalog;

final class DashboardController extends Controller
{
    public function __invoke(ProfileCatalog $profiles): Response
    {
        $statusCounts = JobModel::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $jobs = JobModel::query()
            ->latest('created_at')
            ->limit(12)
            ->get()
            ->map(fn (JobModel $job): array => [
                'id' => (string) $job->id,
                'pipeline' => (string) $job->pipeline_name,
                'status' => (string) $job->status,
                'queue' => (string) $job->queue,
                'attempts' => (int) $job->attempts,
                'current_stage' => $job->current_stage !== null ? (string) $job->current_stage : null,
                'files_changed' => (int) $job->files_changed,
                'files_total' => (int) $job->files_total,
                'error' => $job->error !== null ? (string) $job->error : null,
                'created_at' => $job->created_at?->toISOString(),
                'finished_at' => $job->finished_at?->toISOString(),
            ])
            ->values();

        return Inertia::render('Dashboard', [
            'metrics' => [
                'total' => (int) JobModel::count(),
                'pending' => (int) ($statusCounts['pending'] ?? 0),
                'running' => (int) ($statusCounts['running'] ?? 0),
                'completed' => (int) ($statusCounts['completed'] ?? 0),
                'failed' => (int) ($statusCounts['failed'] ?? 0),
                'cancelled' => (int) ($statusCounts['cancelled'] ?? 0),
            ],
            'jobs' => $jobs,
            'refreshedAt' => now()->toISOString(),
            'profiles' => array_map(
                static fn ($profile): array => [
                    'name' => $profile->name,
                    'description' => $profile->description,
                    'configuration' => $profile->configuration->toArray(),
                ],
                $profiles->all()
            ),
        ]);
    }
}
