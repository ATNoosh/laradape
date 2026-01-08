<?php

namespace App\Metrics;

use Core\Execution\MetricsCollector;

/**
 * Laravel-side adapter for MetricsCollector.
 *
 * Currently a no-op; can later be wired to Prometheus, StatsD, Telescope, etc.
 */
final class LaravelMetricsCollector implements MetricsCollector
{
    public function increment(string $metric, array $labels = []): void
    {
        // TODO: integrate with real metrics backend (Prometheus, StatsD, ...)
    }

    public function observe(string $metric, float $value, array $labels = []): void
    {
        // TODO: integrate with real metrics backend (Prometheus, StatsD, ...)
    }
}


