<?php

namespace App\Console\Commands;

use App\Models\MenuDiskDump;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Command to check that all menu dump ZIP files exist in the filesystem.
 */
class CheckMenuDumps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menus:check-dumps';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check that all menu dump ZIP files are present in the filesystem';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dumps = MenuDiskDump::with('menuDisk.menu.menuSet')->orderBy('id')->get();
        $total = $dumps->count();
        $missing = [];

        $this->info("Checking $total menu dump files...");
        $this->newLine();

        foreach ($dumps as $dump) {
            $path = 'zips/menus/' . $dump->id . '.zip';

            if (! Storage::disk('public')->exists($path)) {
                $missing[] = $dump;
                $label = $dump->menuDisk?->download_basename ?? 'Unknown';
                $this->info("Missing: $path (ID: {$dump->id}, format: {$dump->format}, label: {$label})");
            }
        }

        $this->newLine();

        $found = $total - count($missing);
        $this->info("Results: $found/$total files present");

        if (count($missing) > 0) {
            $this->warn(count($missing) . ' file(s) missing');

            return 1;
        }

        $this->info('All menu dump files are present.');

        return 0;
    }
}
