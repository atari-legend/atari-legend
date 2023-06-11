<?php

namespace App\Console\Commands;

use App\Helpers\DumpHelper;
use App\Models\Dump;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateDumpTrackPictures extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dump:trackpictures';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate track pictures for flux-based dumps';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Dump::where('track_picture', false)
            ->whereIn('format', ['STX', 'SCP'])
            ->each(function (Dump $dump) {
                $this->info('Generating track picture for ' . File::name($dump->download_filename) . ' (id=' . $dump->getKey() . ')', 'v');
                $success = DumpHelper::generateTrackPicture($dump);
                $dump->update(['track_picture' => $success]);
            });

        return Command::SUCCESS;
    }
}
