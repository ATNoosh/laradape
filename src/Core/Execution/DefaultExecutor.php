<?php

namespace Core\Execution;

use Core\Job\Job;
use Core\Job\JobState;
use Core\Job\JobResult;
use Core\Pipeline\PipelineContext;
use Core\Error\RecoverableException;
use Core\Error\FatalException;

final class DefaultExecutor implements Executor
{
    public function __construct(
        private TimeBox $timeBox
    ) {}

    public function execute(Job $job): JobReport
    {
        if ($this->timeBox->isExpired()) {
            return JobReport::timedOut($job->id());
        }

        try {
            $job->pipeline()->run(new PipelineContext());

            if ($this->timeBox->isExpired()) {
                return JobReport::timedOut($job->id());
            }

            return JobReport::completed($job->id());

        } catch (RecoverableException $e) {
            return JobReport::failed($job->id(), $e->getMessage());

        } catch (FatalException $e) {
            return JobReport::failed($job->id(), $e->getMessage());

        } catch (\Throwable) {
            return JobReport::failed($job->id(), 'Unhandled executor error');
        }
    }
}
