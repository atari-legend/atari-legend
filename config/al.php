<?php

// Config specific to Atari Legend

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
    'sndh' => [
        // Where GameMusicController::music() proxies the MP3s from. A config
        // value rather than a literal so a test can point it somewhere it
        // controls: the host is plain HTTP, which is the reason for the proxy
        // in the first place, and a suite that reached for it would be
        // asserting on somebody else's uptime.
        'mp3_base_url' => env('AL_SNDH_MP3_BASE_URL', 'http://sndhrecord.atari.org/mp3/'),
    ],
    'hxcfe' => env('AL_HXCFE', '/hxcfe'),
];
