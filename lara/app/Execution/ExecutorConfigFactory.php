<?php

namespace App\Execution;

use Core\Execution\Config\{
    ExecutorConfig,
    TimingConfig,
    RetryConfig,
    CircuitBreakerConfig
};
use InvalidArgumentException;

final class ExecutorConfigFactory
{
    public static function fromArray(array $config): ExecutorConfig
    {
        self::validate($config);

        return new ExecutorConfig(
            new TimingConfig(
                enabled: (bool) ($config['timing']['enabled'] ?? false),
            ),
            new RetryConfig(
                enabled: (bool) ($config['retry']['enabled'] ?? false),
                maxAttempts: (int) ($config['retry']['max_attempts'] ?? 1),
            ),
            new CircuitBreakerConfig(
                enabled: (bool) ($config['circuit_breaker']['enabled'] ?? false),
                failureThreshold: (int) ($config['circuit_breaker']['failure_threshold'] ?? 5),
                cooldownSeconds: (int) ($config['circuit_breaker']['cooldown_seconds'] ?? 30),
            )
        );
    }

    private static function validate(array $config): void
    {
        $required = [
            'timing.enabled',
            'retry.enabled',
            'retry.max_attempts',
            'circuit_breaker.enabled',
            'circuit_breaker.failure_threshold',
            'circuit_breaker.cooldown_seconds',
        ];

        foreach ($required as $path) {
            if (data_get($config, $path) === null) {
                throw new InvalidArgumentException(
                    "Invalid executor config: missing '{$path}'"
                );
            }
        }

        $maxAttempts = data_get($config, 'retry.max_attempts');
        if ($maxAttempts !== null && $maxAttempts < 1) {
            throw new InvalidArgumentException(
                'retry.max_attempts must be >= 1'
            );
        }

        $failureThreshold = data_get($config, 'circuit_breaker.failure_threshold');
        if ($failureThreshold !== null && $failureThreshold < 1) {
            throw new InvalidArgumentException(
                'circuit_breaker.failure_threshold must be >= 1'
            );
        }

        $cooldownSeconds = data_get($config, 'circuit_breaker.cooldown_seconds');
        if ($cooldownSeconds !== null && $cooldownSeconds < 0) {
            throw new InvalidArgumentException(
                'circuit_breaker.cooldown_seconds must be >= 0'
            );
        }
    }
}

