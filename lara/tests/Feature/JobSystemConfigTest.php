<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Execution\ExecutorConfigFactory;
use InvalidArgumentException;

final class JobSystemConfigTest extends TestCase
{
    public function test_valid_config_creates_executor_config(): void
    {
        $config = ExecutorConfigFactory::fromArray([
            'timing' => [
                'enabled' => true,
            ],
            'retry' => [
                'enabled' => true,
                'max_attempts' => 3,
            ],
            'circuit_breaker' => [
                'enabled' => true,
                'failure_threshold' => 5,
                'cooldown_seconds' => 30,
            ],
        ]);

        $this->assertTrue($config->timing->enabled);
        $this->assertTrue($config->retry->enabled);
        $this->assertSame(3, $config->retry->maxAttempts);
        $this->assertTrue($config->circuitBreaker->enabled);
        $this->assertSame(5, $config->circuitBreaker->failureThreshold);
        $this->assertSame(30, $config->circuitBreaker->cooldownSeconds);
    }

    public function test_invalid_executor_config_fails_fast_missing_timing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid executor config: missing 'timing.enabled'");

        ExecutorConfigFactory::fromArray([
            'retry' => [
                'enabled' => true,
                'max_attempts' => 3,
            ],
            'circuit_breaker' => [
                'enabled' => true,
                'failure_threshold' => 5,
                'cooldown_seconds' => 30,
            ],
        ]);
    }

    public function test_invalid_executor_config_fails_fast_missing_retry_max_attempts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid executor config: missing 'retry.max_attempts'");

        ExecutorConfigFactory::fromArray([
            'timing' => [
                'enabled' => true,
            ],
            'retry' => [
                'enabled' => true,
            ],
            'circuit_breaker' => [
                'enabled' => true,
                'failure_threshold' => 5,
                'cooldown_seconds' => 30,
            ],
        ]);
    }

    public function test_invalid_executor_config_fails_fast_retry_max_attempts_less_than_one(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('retry.max_attempts must be >= 1');

        ExecutorConfigFactory::fromArray([
            'timing' => [
                'enabled' => true,
            ],
            'retry' => [
                'enabled' => true,
                'max_attempts' => 0,
            ],
            'circuit_breaker' => [
                'enabled' => true,
                'failure_threshold' => 5,
                'cooldown_seconds' => 30,
            ],
        ]);
    }

    public function test_invalid_executor_config_fails_fast_circuit_breaker_failure_threshold_less_than_one(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('circuit_breaker.failure_threshold must be >= 1');

        ExecutorConfigFactory::fromArray([
            'timing' => [
                'enabled' => true,
            ],
            'retry' => [
                'enabled' => true,
                'max_attempts' => 3,
            ],
            'circuit_breaker' => [
                'enabled' => true,
                'failure_threshold' => 0,
                'cooldown_seconds' => 30,
            ],
        ]);
    }

    public function test_invalid_executor_config_fails_fast_circuit_breaker_cooldown_seconds_negative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('circuit_breaker.cooldown_seconds must be >= 0');

        ExecutorConfigFactory::fromArray([
            'timing' => [
                'enabled' => true,
            ],
            'retry' => [
                'enabled' => true,
                'max_attempts' => 3,
            ],
            'circuit_breaker' => [
                'enabled' => true,
                'failure_threshold' => 5,
                'cooldown_seconds' => -1,
            ],
        ]);
    }

    public function test_application_config_is_valid(): void
    {
        $raw = config('job-system.executor.decorators');
        $config = ExecutorConfigFactory::fromArray($raw);

        $this->assertNotNull($config);
        $this->assertTrue($config->timing->enabled);
        $this->assertTrue($config->retry->enabled);
        $this->assertGreaterThanOrEqual(1, $config->retry->maxAttempts);
        $this->assertTrue($config->circuitBreaker->enabled);
        $this->assertGreaterThanOrEqual(1, $config->circuitBreaker->failureThreshold);
        $this->assertGreaterThanOrEqual(0, $config->circuitBreaker->cooldownSeconds);
    }
}

