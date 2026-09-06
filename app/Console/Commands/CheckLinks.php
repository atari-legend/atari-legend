<?php

namespace App\Console\Commands;

use App\Models\Link;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Command to check links from the links table, and mark them as inactive
 * if the connection failed.
 */
class CheckLinks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'links:check {--timeout=5 : Connection timeout in seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the links for dead links';

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
        $total = Link::count();
        $current = 0;

        Link::all()
            ->sortBy('name')
            ->each(function ($link) use (&$current, $total) {
                $current++;
                $this->info("Checking $current/$total: $link->name ($link->url)");

                try {
                    $response = Http::timeout(intval($this->option('timeout')))
                        ->get($link->url);

                    if ($response->failed()) {
                        $this->error("\tError: " . $response->status());
                        $link->inactive = 1;
                    } else {
                        $this->comment("\tOK");
                        $link->inactive = 0;
                    }
                } catch (Exception $e) {
                    $this->error("\tError: " . $e->getMessage());
                    $link->inactive = 1;
                }

                $link->save();
            });

        return 0;
    }
}
