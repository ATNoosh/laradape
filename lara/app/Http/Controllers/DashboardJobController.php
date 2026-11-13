<?php

namespace App\Http\Controllers;

use App\Execution\JobChainDetailsReader;
use App\Execution\JobDetailsReader;
use App\Execution\JobEventReader;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardJobController extends Controller
{
    public function __invoke(
        string $id,
        JobDetailsReader $jobs,
        JobChainDetailsReader $chains,
        JobEventReader $events
    ): Response {
        $details = $jobs->find($id);

        abort_if($details === null, 404);

        return Inertia::render('JobDetails', [
            'job' => $details,
            'chain' => $details->rootJobId === null ? null : $chains->find($id),
            'events' => $events->forJob($id),
        ]);
    }
}
