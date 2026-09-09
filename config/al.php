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
    // Folder holding the HxC Floppy Emulator binary and its .so files.
    // DumpHelper appends '/hxcfe' to this and runs the binary with the folder as
    // its working directory, which the RPATH of '.' on the binary requires - so
    // the layout has to stay flat. Not in the repository, like the unice68 and
    // icecat binaries beside it; resources/bin is simply where this project
    // keeps the native tools it shells out to.
    'hxcfe' => env('AL_HXCFE', base_path('resources/bin/hxcfe')),
];
