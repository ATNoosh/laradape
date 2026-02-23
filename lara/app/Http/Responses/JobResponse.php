<?php

namespace App\Http\Responses;

use Core\Execution\JobReport;

final class JobResponse implements \JsonSerializable
{
    public function __construct(
        private string $jobId,
        private string $status,
        private ?string $message = null,
        private array $meta = []
    ) {}

    public static function fromReport(JobReport $report): self
    {
        $status = $report->isSkipped()
            ? 'skipped'
            : $report->result()->state();

        $message = $report->isSkipped()
            ? 'Job already running or completed'
            : $report->result()->message();

        return new self(
            $report->jobId()->value(),
            $status,
            $message
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'job_id' => $this->jobId,
            'status' => $this->status,
            'message' => $this->message,
            'meta' => $this->meta,
        ];
    }
}
