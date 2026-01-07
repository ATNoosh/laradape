<?php

namespace Core\Job;

final class JobResult
{
    public const STATE_COMPLETED = 'completed';
    public const STATE_FAILED    = 'failed';
    public const STATE_TIMED_OUT = 'timed_out';

    private function __construct(
        private string $state,
        private ?string $message = null
    ) {}

    /* ---------- Factories ---------- */

    public static function completed(): self
    {
        return new self(self::STATE_COMPLETED);
    }

    public static function failed(string $message): self
    {
        return new self(self::STATE_FAILED, $message);
    }

    public static function timedOut(?string $message = null): self
    {
        return new self(
            self::STATE_TIMED_OUT,
            $message ?? 'Execution time exceeded'
        );
    }

    /* ---------- Accessors ---------- */

    public function state(): string
    {
        return $this->state;
    }

    public function message(): ?string
    {
        return $this->message;
    }

    /* ---------- Intent methods ---------- */

    public function isCompleted(): bool
    {
        return $this->state === self::STATE_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->state === self::STATE_FAILED;
    }

    public function isTimedOut(): bool
    {
        return $this->state === self::STATE_TIMED_OUT;
    }

    public function isSuccessful(): bool
    {
        return $this->isCompleted();
    }
}
