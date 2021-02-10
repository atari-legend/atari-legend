<?php

// Config specific to AtariLegend

return [
    'legacy' => [
        'base_url' => env('AL_LEGACY_BASE_URL', 'http://legacy.atarilegend.com'),
    ],
    'analytics' => [
        'matomo' => [
            'id' => env('MATOMO_ID'),
        ],
    ],
    'stonish' => [
        'root' => env('STONISH_ROOT'),
    ],
];
