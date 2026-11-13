<?php

namespace App\Execution;

final class JobChainDetails implements \JsonSerializable
{
    /** @param list<JobDetails> $jobs */
    public function __construct(
        public readonly string $rootJobId,
        public readonly string $status,
        public readonly int $totalHops,
        public readonly int $completedHops,
        public readonly int $failedHops,
        public readonly int $cancelledHops,
        public readonly ?int $currentHop,
        public readonly array $jobs,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'root_job_id' => $this->rootJobId,
            'status' => $this->status,
            'total_hops' => $this->totalHops,
            'completed_hops' => $this->completedHops,
            'failed_hops' => $this->failedHops,
            'cancelled_hops' => $this->cancelledHops,
            'current_hop' => $this->currentHop,
            'jobs' => $this->jobs,
        ];
    }
}
