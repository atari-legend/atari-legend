<?php

namespace App\Console\Commands;

use App\Models\Sndh;
use App\Models\SndhArchive;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Download and extract the SNDH archives.
 */
class FetchSNDH extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sndh:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download and unpack SNDH archives';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        SndhArchive::all()->each(function ($archive) {
            $path = Storage::disk('public')->path('sndh');
            if (! is_dir($path)) {
                mkdir($path, 0777, true);
            }

            $zipPath = $path . '/' . $archive->getKey() . '.zip';
            if (! is_file($zipPath)) {
                $this->info('Downloading SNDH collection from ' . $archive->download_url . ' into ' . $zipPath);

                $response = Http::connectTimeout(60)
                    ->timeout(60)
                    ->sink($zipPath)
                    ->get($archive->download_url);

                if ($response->ok() !== true) {
                    $this->error('Error downloading archive ' . $archive->download_url . ': ' . $response->status());
                    @unlink($zipPath);

                    return 1;
                }
            } else {
                $this->info('Archive file ' . $zipPath . ' already exists, skipping download');
            }

            $extractPath = $path . '/' . $archive->getKey();

            if (! is_dir($extractPath)) {
                $zip = new ZipArchive();
                if ($zip->open($zipPath) !== true) {
                    $this->error('Error opening ZIP file: ' . $zip->getStatusString());

                    return 1;
                }
                $zip->extractTo($extractPath);
                $zip->close();
                $this->info('Extracted SNDH archive ' . $archive->getKey() . ' into ' . $extractPath);
            } else {
                $this->info('Extracted directory .' . $extractPath . ' already exists, skipping extraction');
            }
        });
    }
}
