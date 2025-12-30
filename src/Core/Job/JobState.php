<?php

namespace Core\Job;

final class JobState
{
    public const PENDING   = 'pending';
    public const RUNNING   = 'running';
    public const FAILED    = 'failed';
    public const COMPLETED = 'completed';
    public const TIMED_OUT = 'timed_out';
}
