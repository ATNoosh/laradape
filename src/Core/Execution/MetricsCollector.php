<?php

namespace Core\Execution;

interface MetricsCollector
{
    /**
     * Increment a counter metric.
     *
     * @param string $metric Metric name (e.g. "jobs_started_total")
     * @param array<string, string> $labels Optional labels for the series
     */
    public function increment(string $metric, array $labels = []): void;

    /**
     * Observe a value for a histogram/gauge metric.
     *
     * @param string $metric Metric name (e.g. "job_duration_ms")
     * @param float $value Observed value
     * @param array<string, string> $labels Optional labels for the series
     */
    public function observe(string $metric, float $value, array $labels = []): void;
}


