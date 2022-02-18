<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

/**
 * Import SNDH data by parsing SNDH files headers and generate
 * a JSON file containing a description of all songs.
 */
class ImportSNDH extends Command
{
    /**
     * URL to download the SNDH archive from.
     */
    const SNDH_ZIP_URL = 'http://sndh.atari.org/files/sndh45lf.zip';

    /**
     * Path to store the JSON file containing all songs.
     */
    const SONGS_JSON_PATH = 'resources/sndh/songs-4.5.json';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sndh:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import SNDH collection';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Downloading SNDH collection from ' . ImportSNDH::SNDH_ZIP_URL);
        $response = Http::get(ImportSNDH::SNDH_ZIP_URL);
        if ($response->ok() !== true) {
            $this->error('Error downloading ZIP file: ' . $response->status() . ', ' . $response->body());

            return 1;
        }

        $zipFile = tempnam(sys_get_temp_dir(), 'ImportSNDH-');
        file_put_contents($zipFile, $response->body());

        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            $this->error('Error opening ZIP file: ' . $zip->getStatusString());

            return 1;
        }

        $this->info('Extracting to ' . sys_get_temp_dir());
        $zip->extractTo(sys_get_temp_dir());
        $zip->close();

        $sndhDir = sys_get_temp_dir() . '/sndh_lf/';

        if (file_exists($sndhDir) !== true) {
            $this->error("Folder {$sndhDir} did not exist after ZIP extraction");

            return 1;
        }

        $directoryIterator = new RecursiveDirectoryIterator($sndhDir);
        $iterator = new RecursiveIteratorIterator($directoryIterator);

        $songData = [];

        foreach ($iterator as $fileInfo) {
            if (strtolower($fileInfo->getExtension()) === 'sndh') {
                $this->info('Processing ' . $fileInfo->getFileinfo());

                $tmpFile = tempnam(sys_get_temp_dir(), 'sndh-');

                $file = $fileInfo->openFile();
                $header = $file->fread(4);
                $file->close();

                if ($header === 'ICE!') {
                    // Call unice68 via a shell script. The unice68 binary
                    // must exist on the system
                    if (exec(base_path('resources/bin/unice68.sh \'' . $fileInfo->getPathname() . '\' ' . $tmpFile)) === false) {
                        $this->warn('Error unpacking ' . $fileInfo->getPathname());
                    }
                } else {
                    copy($file->getPathname(), $tmpFile);
                }

                $file = fopen($tmpFile, 'r');
                fseek($file, 12);
                $magic = fread($file, 4);
                if ($magic !== 'SNDH') {
                    $this->warn('File ' . $fileInfo->getPathname() . ' does not have the SNDH marker: ' . $magic);
                    continue;
                }

                $tag = fread($file, 4);
                $data = [];
                $subTunes = 0;
                do {
                    switch (true) {
                        case $tag === 'TITL':
                            $data['title'] = ImportSNDH::readUntilNull($file);
                            break;
                        case $tag === 'COMM':
                            $data['composer'] = ImportSNDH::readUntilNull($file);
                            break;
                        case $tag === 'RIPP':
                            $data['ripper'] = ImportSNDH::readUntilNull($file);
                            break;
                        case $tag === 'CONV':
                            $data['converter'] = ImportSNDH::readUntilNull($file);
                            break;
                        case preg_match('/##../', $tag):
                            ImportSNDH::readUntilNull($file);
                            $subTunes = intval(str_replace('#', '', $tag));
                            $data['subtunes'] = $subTunes;
                            break;
                        case preg_match('/T[A-D]../', $tag):
                            ImportSNDH::readUntilNull($file);
                            break;
                        case preg_match('/!V../', $tag):
                            ImportSNDH::readUntilNull($file);
                            break;
                        case $tag === 'YEAR':
                            $year = ImportSNDH::readUntilNull($file);
                            if ($year !== '') {
                                $data['year'] = intval($year);
                            }
                            break;
                        case preg_match('/#!../', $tag):
                            $defaultSubtune = ImportSNDH::readUntilNull($file);
                            if ($defaultSubtune !== '') {
                                $data['defaultSubtune'] = intval($defaultSubtune);
                            }
                            break;
                        case $tag === '#!SN':
                            $data['subtuneTitles'] = [];
                            for ($i = 0; $i < $subTunes; $i++) {
                                $title = ImportSNDH::readUntilNull($file);
                                $data['subtuneTitles'][$i] = $title;
                            }
                            break;
                        case $tag === 'TIME':
                            $data['times'] = [];
                            for ($i = 0; $i < $subTunes; $i++) {
                                $time = fgetc($file) . fgetc($file);
                                $time = unpack('ntime', $time);
                                array_push($data['times'], $time['time']);
                            }
                            break;
                        case $tag === 'HDNS':
                            $tag = null;
                            break;
                        default:
                            $tag = null;
                            break;
                    }

                    if ($tag !== null) {
                        $tag = fread($file, 4);
                    }
                } while ($tag !== null);

                fclose($file);

                if (count($data) > 0) {
                    $songData[str_replace($sndhDir, '', $fileInfo->getPath()) . '/' . $fileInfo->getBasename('.sndh')] = $data;
                } else {
                    $this->warn('No data found for ' . $fileInfo->getPathname());
                    exit(1);
                }
            }
        }

        ksort($songData);

        file_put_contents(ImportSNDH::SONGS_JSON_PATH, json_encode($songData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return 0;
    }

    /**
     * Naively read a file character by character until encountering a NULL character.
     *
     * Takes care of conversion from CP-437 to UTF-8.
     *
     * @param  resource  $stream  File to read
     * @return string read data
     */
    private static function readUntilNull($stream): string
    {
        $buf = '';
        do {
            $c = fgetc($stream);
            if ($c !== "\0") {
                $buf .= iconv('cp437', 'UTF-8', $c);
            }
        } while ($c !== "\0" && $c !== false);

        return $buf;
    }
}
