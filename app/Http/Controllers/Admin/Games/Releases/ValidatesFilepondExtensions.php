<?php

namespace App\Http\Controllers\Admin\Games\Releases;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Sopamo\LaravelFilepond\Filepond;

/**
 * The upload panels under a release post FilePond server ids rather than files,
 * so there is no uploaded file left for the `mimes` rule to look at. The
 * extension comes off the temporary file instead - lowercased, because FilePond
 * names that file after the one the client sent, case and all.
 */
trait ValidatesFilepondExtensions
{
    /**
     * Resolve the posted server ids to temporary paths, and reject the whole
     * batch if any file's extension is not one the target column accepts.
     *
     * @param  array  $serverIds  What the form posted in its `file` field
     * @param  array  $extensions  Extensions the target column accepts
     * @return \Illuminate\Support\Collection Temporary path => extension
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function filepondExtensions(array $serverIds, array $extensions): Collection
    {
        $filepond = app(Filepond::class);

        $files = collect($serverIds)
            ->filter()
            ->mapWithKeys(function ($serverId) use ($filepond) {
                $path = $filepond->getPathFromServerId($serverId);

                return [$path => strtolower(File::extension(Storage::path($path)))];
            });

        $rejected = $files->reject(fn ($ext) => in_array($ext, $extensions, true));

        if ($rejected->isNotEmpty()) {
            throw ValidationException::withMessages([
                'file' => 'Unsupported file extension: ' . $rejected->unique()->implode(', '),
            ]);
        }

        return $files;
    }
}
