<?php

namespace Tests\Feature\Admin\Games\Releases;

use Illuminate\Support\Facades\Storage;
use Sopamo\LaravelFilepond\Filepond;

/**
 * The upload panels under a release do not receive the file itself: the browser
 * has already handed it to FilePond, and the form only posts back the encrypted
 * "server id" that stands for the temporary file. Faking `UploadedFile` would
 * therefore miss the controllers entirely, so these tests stage the temporary
 * file on the local disk and post the matching server id.
 */
trait InteractsWithFilepond
{
    /**
     * Stage a file the way a finished FilePond upload leaves it, and return the
     * server id the form would post for it.
     */
    protected function filepondServerId(string $filename, string $contents = 'dump'): string
    {
        $path = 'filepond/' . $filename;

        Storage::put($path, $contents);

        return app(Filepond::class)->getServerIdFromPath($path);
    }
}
