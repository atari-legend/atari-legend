<?php

namespace App\Console\Commands;

use App\Models\SndhArchive;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Parse SNDH files headers and generate
 * a JSON file containing a description of all songs.
 * Assumes that the SNDH files already exist (See the FetchSNDH command).
 */
class GenerateSNDHJson extends Command
{
    /**
     * Path to store the JSON file containing all songs.
     */
    const SONGS_JSON_PATH = 'resources/sndh';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sndh:generate-json';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate JSON metadata for SNDH archives';

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
        SndhArchive::all()->each(function ($archive) {
            $sndhDir = Storage::disk('public')->path('sndh/' . $archive->getKey() . '/sndh_lf');

            if (file_exists($sndhDir) !== true) {
                $this->error("Folder {$sndhDir} does not exist");

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

                    if ($header === 'ICE!') {
                        // Call unice via a shell script.
                        $output = [];
                        $cmd = 'resources/bin/unice.sh' . ' \'' . $fileInfo->getPathname() . '\' ' . $tmpFile;
                        if (exec($cmd, $output) === false) {
                            $this->warn('Error unpacking ' . $fileInfo->getPathname() . '. Output: ' . join("\n", $output));
                        }
                    } else {
                        copy($file->getPathname(), $tmpFile);
                    }
                    $file = null; // No close method for SplFileObject, unset to close

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
                                $data['title'] = GenerateSNDHJson::readUntilNull($file);
                                break;
                            case $tag === 'COMM':
                                $data['composer'] = GenerateSNDHJson::readUntilNull($file);
                                break;
                            case $tag === 'RIPP':
                                $data['ripper'] = GenerateSNDHJson::readUntilNull($file);
                                break;
                            case $tag === 'CONV':
                                $data['converter'] = GenerateSNDHJson::readUntilNull($file);
                                break;
                            case preg_match('/##../', $tag):
                                GenerateSNDHJson::readUntilNull($file);
                                $subTunes = intval(str_replace('#', '', $tag));
                                $data['subtunes'] = $subTunes;
                                break;
                            case preg_match('/T[A-D]../', $tag):
                                GenerateSNDHJson::readUntilNull($file);
                                break;
                            case preg_match('/!V../', $tag):
                                GenerateSNDHJson::readUntilNull($file);
                                break;
                            case $tag === 'YEAR':
                                $year = GenerateSNDHJson::readUntilNull($file);
                                if ($year !== '') {
                                    $data['year'] = intval($year);
                                }
                                break;
                            case preg_match('/#!../', $tag):
                                $defaultSubtune = GenerateSNDHJson::readUntilNull($file);
                                if ($defaultSubtune !== '') {
                                    $data['defaultSubtune'] = intval($defaultSubtune);
                                }
                                break;
                            case $tag === '#!SN':
                                $data['subtuneTitles'] = [];
                                for ($i = 0; $i < $subTunes; $i++) {
                                    $title = GenerateSNDHJson::readUntilNull($file);
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

            file_put_contents(GenerateSNDHJson::SONGS_JSON_PATH . '/songs-' . $archive->getKey() . '.json', json_encode($songData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        });

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
