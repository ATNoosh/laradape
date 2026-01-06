<?php

return [

    'executor' => [

        'decorators' => [

            'timing' => [
                'enabled' => true,
            ],

            'retry' => [
                'enabled' => true,
                'max_attempts' => 3,
            ],

            'circuit_breaker' => [
                'enabled' => true,
                'failure_threshold' => 2,
                'cooldown_seconds' => 60,
            ],

        ],

    ],

];
