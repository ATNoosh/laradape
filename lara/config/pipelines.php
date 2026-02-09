<?php

use Core\Pipeline\DefaultPipeline;

return [

    /*
    |--------------------------------------------------------------------------
    | Pipeline registry
    |--------------------------------------------------------------------------
    |
    | name => factory (callable)
    |
    */

    'map' => [
        'default' => fn () => new DefaultPipeline([]),
    ],
];
