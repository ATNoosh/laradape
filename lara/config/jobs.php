<?php

return [
    // A real project may need several minutes per upgrade hop.
    'timeout_seconds' => (int) env('JOB_TIMEOUT_SECONDS', 600),

    'retry' => [
        'max_attempts' => 3,
        'delay_ms' => 0,
    ],

    'http' => [
        'rate_limit' => '60,1',
        'api_key' => null,
        'idempotency_ttl_seconds' => 300,
    ],
];
