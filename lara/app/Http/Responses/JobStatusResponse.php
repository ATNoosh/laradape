<?php

namespace App\Http\Responses;

use Core\Job\JobStatus;
use Core\ValueObject\JobId;

final class JobStatusResponse implements \JsonSerializable
{
    public function __construct(
        private JobId $jobId,
        private JobStatus $status
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'job_id' => $this->jobId->value(),
            'status' => $this->status->value,
        ];
    }
}
