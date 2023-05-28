<?php

namespace App\Helpers;

use App\Models\Dump;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
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
     * @param $dump \App\Model\Dump Dump to store the ZIP file for.
     * @param $path string Path to the dump.
     */
    public static function storeDump(Dump $dump, string $path): void
    {
        $zipPath = Storage::disk('public')->path($dump->path);

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);

        $zip->addFile($path, $dump->getKey() . '.' . strtolower($dump->format));
        $zip->close();
    }
}
