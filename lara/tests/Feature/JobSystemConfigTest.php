<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Execution\ExecutorConfigFactory;
use InvalidArgumentException;

final class JobSystemConfigTest extends TestCase
{
    private function validBaseConfig(): array
    {
        return [
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
        ];
    }

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

    // ============================================
    // Property-Based Tests
    // ============================================

    /**
     * @dataProvider invalidRetryAttempts
     */
    public function test_retry_max_attempts_must_be_positive(int $value): void
    {
        $config = $this->validBaseConfig();
        $config['retry']['max_attempts'] = $value;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('retry.max_attempts must be >= 1');

        ExecutorConfigFactory::fromArray($config);
    }

    public static function invalidRetryAttempts(): array
    {
        return array_map(
            fn ($v) => [$v],
            range(-20, 0)
        );
    }

    /**
     * @dataProvider validRetryAttempts
     */
    public function test_valid_retry_attempts_always_work(int $value): void
    {
        $config = $this->validBaseConfig();
        $config['retry']['max_attempts'] = $value;

        $executorConfig = ExecutorConfigFactory::fromArray($config);

        $this->assertSame($value, $executorConfig->retry->maxAttempts);
    }

    public static function validRetryAttempts(): array
    {
        return array_map(
            fn ($v) => [$v],
            range(1, 50)
        );
    }

    /**
     * @dataProvider invalidFailureThresholds
     */
    public function test_failure_threshold_must_be_positive(int $value): void
    {
        $config = $this->validBaseConfig();
        $config['circuit_breaker']['failure_threshold'] = $value;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('circuit_breaker.failure_threshold must be >= 1');

        ExecutorConfigFactory::fromArray($config);
    }

    public static function invalidFailureThresholds(): array
    {
        return array_map(
            fn ($v) => [$v],
            range(-20, 0)
        );
    }

    /**
     * @dataProvider validFailureThresholds
     */
    public function test_valid_failure_thresholds_always_work(int $value): void
    {
        $config = $this->validBaseConfig();
        $config['circuit_breaker']['failure_threshold'] = $value;

        $executorConfig = ExecutorConfigFactory::fromArray($config);

        $this->assertSame($value, $executorConfig->circuitBreaker->failureThreshold);
    }

    public static function validFailureThresholds(): array
    {
        return array_map(
            fn ($v) => [$v],
            range(1, 50)
        );
    }

    /**
     * @dataProvider invalidCooldowns
     */
    public function test_cooldown_seconds_cannot_be_negative(int $value): void
    {
        $config = $this->validBaseConfig();
        $config['circuit_breaker']['cooldown_seconds'] = $value;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('circuit_breaker.cooldown_seconds must be >= 0');

        ExecutorConfigFactory::fromArray($config);
    }

    public static function invalidCooldowns(): array
    {
        return array_map(
            fn ($v) => [$v],
            range(-30, -1)
        );
    }

    /**
     * @dataProvider validCooldowns
     */
    public function test_valid_cooldown_seconds_always_work(int $value): void
    {
        $config = $this->validBaseConfig();
        $config['circuit_breaker']['cooldown_seconds'] = $value;

        $executorConfig = ExecutorConfigFactory::fromArray($config);

        $this->assertSame($value, $executorConfig->circuitBreaker->cooldownSeconds);
    }

    public static function validCooldowns(): array
    {
        return array_map(
            fn ($v) => [$v],
            range(0, 100)
        );
    }

    /**
     * @dataProvider missingConfigPaths
     */
    public function test_missing_required_config_fails(string $path): void
    {
        $config = $this->validBaseConfig();
        
        // Remove the key using dot notation
        $keys = explode('.', $path);
        $current = &$config;
        for ($i = 0; $i < count($keys) - 1; $i++) {
            if (!isset($current[$keys[$i]])) {
                return; // Path doesn't exist, skip
            }
            $current = &$current[$keys[$i]];
        }
        unset($current[$keys[count($keys) - 1]]);

        $this->expectException(InvalidArgumentException::class);

        ExecutorConfigFactory::fromArray($config);
    }

    public static function missingConfigPaths(): array
    {
        return [
            ['timing.enabled'],
            ['retry.enabled'],
            ['retry.max_attempts'],
            ['circuit_breaker.enabled'],
            ['circuit_breaker.failure_threshold'],
            ['circuit_breaker.cooldown_seconds'],
        ];
    }

    public function test_random_valid_configs_never_fail(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $config = [
                'timing' => [
                    'enabled' => (bool) random_int(0, 1),
                ],
                'retry' => [
                    'enabled' => (bool) random_int(0, 1),
                    'max_attempts' => random_int(1, 20),
                ],
                'circuit_breaker' => [
                    'enabled' => (bool) random_int(0, 1),
                    'failure_threshold' => random_int(1, 10),
                    'cooldown_seconds' => random_int(0, 60),
                ],
            ];

            $executorConfig = ExecutorConfigFactory::fromArray($config);

            $this->assertInstanceOf(
                \Core\Execution\Config\ExecutorConfig::class,
                $executorConfig
            );

            // Verify invariants
            $this->assertGreaterThanOrEqual(1, $executorConfig->retry->maxAttempts);
            $this->assertGreaterThanOrEqual(1, $executorConfig->circuitBreaker->failureThreshold);
            $this->assertGreaterThanOrEqual(0, $executorConfig->circuitBreaker->cooldownSeconds);
        }
    }
}

