<?php

namespace Core\Repository;

use Core\Job\Job;
use Core\ValueObject\JobId;

interface JobRepository
{
    public function get(JobId $id): Job;

    public function save(Job $job): void;

    public function exists(JobId $id): bool;

}
