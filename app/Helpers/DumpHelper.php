<?php

namespace App\Helpers;

use App\Models\Dump;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic;
use ZipArchive;

/**
 * Helper for dumps.
 */
class DumpHelper
{
    /**
     * Detect the format of a dump.
     *
     * @param  string  $path  Path of the dump to detect the format of.
     * @return string Format of the dump.
     */
    public static function detectFormat(string $path): ?string
    {
        $format = null;

        if (file_exists($path)) {
            $f = fopen($path, 'rb');
            if ($f) {
                $header = fread($f, 4);
                if ($header[0] === 'S' && $header[1] === 'C' && $header[2] === 'P') {
                    $format = 'SCP';
                } elseif ($header[0] === 0x0E && $header[1] === 0x0F) {
                    $format = 'MSA';
                } elseif ($header[0] === 'R' && $header[1] === 'S' && $header[2] === 'Y' && $header[3] === 0x00) {
                    $format = 'STX';
                }

                fclose($f);
            }
        }

        // If the format could not be detected, naively return the file extension
        if ($format === null) {
            $ext = File::extension(strtolower($path));
            $format = strtoupper($ext);
        }

        return $format;
    }

    /**
     * Store the dump of a game in a ZIP file.
     *
     * @param $dump \App\Models\Dump Dump to store the ZIP file for.
     * @param $path string Path to the dump.
     */
    public static function storeDump(Dump $dump, string $path): void
    {
        $zipPath = Storage::disk('public')->path($dump->path);

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);

        $filenameInZip = $dump->getKey() . '.' . strtolower($dump->format);
        $zip->addFile($path, $filenameInZip);
        // Use compression level of 5 to reduce zipping time
        $zip->setCompressionName($filenameInZip, ZipArchive::CM_DEFLATE, 5);
        $zip->close();
    }

    /**
     * Get the actual dump from the containing ZIP.
     *
     * @param $dump \App\Models\Dump Dump to get.
     * @param $path string Where to store the dump.
     */
    public static function getDump(Dump $dump, string $path): void
    {
        $zip = new ZipArchive();
        $zip->open(Storage::disk('public')->path($dump->path));
        $data = $zip->getFromIndex(0);
        file_put_contents($path, $data);
        $zip->close();
    }

    /**
     * Generate the track picture of a dump with the HxC Floppy Emulator tool.
     *
     * @param @dump \App\Models\Dump Dump to generate the track picture for.
     * @return bool true if a track picture was generated, false otherwise.
     */
    public static function generateTrackPicture(Dump $dump): bool
    {
        $success = false;

        Storage::disk('public')->makeDirectory(Dump::TRACKPICTURES_DIRECTORY);

        if (file_exists(config('al.hxcfe'))) {
            $tmpDump = sys_get_temp_dir() . '/' . $dump->getKey() . '.' . $dump->format;
            DumpHelper::getDump($dump, $tmpDump);

            $tmpBmp = sys_get_temp_dir() . '/' . File::name($dump->download_filename) . '.bmp';

            $cmd = [
                config('al.hxcfe') . '/hxcfe',
                '-finput:' . $tmpDump,
                '-foutput:' . $tmpBmp,
                '-conv:BMP_DISK_IMAGE',
            ];

            // Run HxC FE tool to generate image in BMP format
            $process = proc_open($cmd, [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ], $pipes, config('al.hxcfe'));

            if (is_resource($process)) {
                $output = stream_get_contents($pipes[1]);
                $rc = proc_close($process);

                if ($rc === 0) {
                    // Convert BMP to PNG and store it
                    ImageManagerStatic::make($tmpBmp)
                        ->save(Storage::disk('public')->path(Dump::TRACKPICTURES_DIRECTORY . '/' . $dump->getKey() . '.png'));

                    $success = true;
                } else {
                    Log::warning('Failed to execute hxcfe from ' . config('al.hxcfe') . ': ' . $rc . "\n" . join("\n", $output));
                }
            }

            unlink($tmpDump);
            unlink($tmpBmp);

            return $success;
        }

        return false;
    }
}
