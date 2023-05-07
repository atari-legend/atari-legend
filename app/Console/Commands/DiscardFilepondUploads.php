<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DiscardFilepondUploads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'filepond:discard
        {--expiry= : Uploads older than the expiry (in days) will be discarded. Defaults to 1d.}
        {--delete : Actually discard the uploads. By default nothing is deleted.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Discard expired Filepond uploads';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->option('expiry') && ! is_numeric($this->option('expiry'))) {
            $this->error('Invalid expiry "' . $this->option('expiry') . '". It must be a number of days');

            return Command::FAILURE;
        }

        $expiry = $this->option('expiry') ?: 1;
        $maxDate = Carbon::now()->subDays($expiry);
        $this->info("Discarding Filepond uploads older than {$expiry} days (before {$maxDate})");

        collect(Storage::disk(config('filepond.temporary_files_disk'))
            ->directories(config('filepond.temporary_files_path')))
            ->filter(fn ($item) => $item !== config('filepond.temporary_files_path') . '/chunks')
            ->each(function ($dir) use ($maxDate) {
                $lm = new Carbon(Storage::disk(config('filepond.temporary_files_disk'))->lastModified($dir));
                if ($lm->lessThan($maxDate)) {
                    $msg = "Folder {$dir}' last modified {$lm} is before {$maxDate},";
                    if ($this->option('delete')) {
                        Storage::disk(config('filepond.temporary_files_disk'))->deleteDirectory($dir);
                        $this->info("{$msg} deleted");
                    } else {
                        $this->info("{$msg} would have been deleted");
                    }
                } else {
                    $this->info("Folder '{$dir}' last modified {$lm} is after {$maxDate}, skipping");
                }
            });

        return Command::SUCCESS;
    }
}
