<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Workers\JobWorker;
use Core\ValueObject\JobId;

final class RunJobCommand extends Command
{
    protected $signature = 'job:run {jobId}';
    protected $description = 'Run a job by id';

    public function __construct(
        private JobWorker $worker
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $jobId = new JobId($this->argument('jobId'));

        $report = $this->worker->run($jobId);

        if ($report->result()->isCompleted()) {
            $this->info('Job completed successfully');
            return self::SUCCESS;
        }

        if ($report->isSkipped()) {
            $this->warn('Job skipped (already running or completed)');
            return self::SUCCESS;
        }

        $this->error('Job failed: ' . $report->result()->message());
        return self::FAILURE;
    }
}
