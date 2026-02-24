<?php

return [
    'timeout_seconds' => 30,

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
