<?php

namespace Core\Execution;

final class CircuitBreaker
{
    private int $failures = 0;
    private CircuitState $state = CircuitState::CLOSED;
    private ?float $openedAt = null;

    public function __construct(
        private int $failureThreshold,
        private int $cooldownSeconds
    ) {}

    public function canExecute(): bool
    {
        if ($this->state === CircuitState::OPEN) {
            if ((microtime(true) - $this->openedAt) >= $this->cooldownSeconds) {
                $this->state = CircuitState::HALF_OPEN;
                return true;
            }
            return false;
        }

        return true;
    }

    public function reportSuccess(): void
    {
        $this->failures = 0;
        $this->state = CircuitState::CLOSED;
        $this->openedAt = null;
    }

    public function reportFailure(): void
    {
        $this->failures++;

        if ($this->failures >= $this->failureThreshold) {
            $this->state = CircuitState::OPEN;
            $this->openedAt = microtime(true);
        }
    }

    public function state(): CircuitState
    {
        return $this->state;
    }
}
