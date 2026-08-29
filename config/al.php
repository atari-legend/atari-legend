<?php

// Config specific to Atari Legend

return [
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
    // Folder holding the HxC Floppy Emulator binary and its .so files.
    // DumpHelper appends '/hxcfe' to this and runs the binary with the folder as
    // its working directory, which the RPATH of '.' on the binary requires - so
    // the layout has to stay flat. Not in the repository, like the unice68 and
    // icecat binaries beside it; resources/bin is simply where this project
    // keeps the native tools it shells out to.
    'hxcfe' => env('AL_HXCFE', base_path('resources/bin/hxcfe')),
];
