<?php

// Config specific to AtariLegend

return [
    'legacy' => [
        'base_url' => env('AL_LEGACY_BASE_URL', 'http://legacy.atarilegend.com'),
    ],
    'analytics' => [
        'id'     => env('GOOGLE_ANALYTICS_ID'),
        'matomo' => [
            'id' => env('MATOMO_ID'),
        ],
    ],
];
