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

    public function execute(Job $job): ExecutionReport
    {
        $context = new PipelineContext();

        try {
            if ($this->timeBox->isExpired()) {
                return new ExecutionReport(
                    new JobResult(JobState::TIMED_OUT, 'Execution time exceeded before start')
                );
            }

            $job->pipeline()->run($context);

            if ($this->timeBox->isExpired()) {
                return new ExecutionReport(
                    new JobResult(JobState::TIMED_OUT, 'Execution time exceeded after pipeline run')
                );
            }

            return new ExecutionReport(
                new JobResult(JobState::COMPLETED)
            );

        } catch (RecoverableException $e) {
            return new ExecutionReport(
                new JobResult(JobState::FAILED, $e->getMessage())
            );

        } catch (FatalException $e) {
            return new ExecutionReport(
                new JobResult(JobState::FAILED, $e->getMessage())
            );

        } catch (\Throwable $e) {
            return new ExecutionReport(
                new JobResult(JobState::FAILED, 'Unhandled executor error')
            );
        }
    }
}
