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

    public function execute(Job $job): JobReport
    {
        $timeBox = new TimeBox(time(), 60);
        $context = new PipelineContext();

        try {
            if ($timeBox->isExpired()) {
                return new JobReport(
                    $job->id(),
                    new JobResult(JobState::TIMED_OUT, 'Execution time exceeded before start')
                );
            }

            $job->pipeline()->run($context);

            if ($timeBox->isExpired()) {
                return new JobReport(
                    $job->id(),
                    new JobResult(JobState::TIMED_OUT, 'Execution time exceeded after pipeline run')
                );
            }

            return new JobReport(
                $job->id(),
                new JobResult(JobState::COMPLETED)
            );
        } catch (RecoverableException $e) {
            return new JobReport(
                $job->id(),
                new JobResult(JobState::FAILED, $e->getMessage())
            );
        } catch (FatalException $e) {
            return new JobReport(
                $job->id(),
                new JobResult(JobState::FAILED, $e->getMessage())
            );
        } catch (\Throwable $e) {
            return new JobReport(
                $job->id(),
                new JobResult(JobState::FAILED, 'Unhandled executor error')
            );
        }
    }
}
