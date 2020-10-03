<?php

namespace App\Console\Commands;

use App\Website;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Command to check links from the website table, and mark them as inactive
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
    protected $description = 'Check the websites / links for dead links';

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
        $total = Website::count();
        $current = 0;

        Website::all()
            ->sortBy('website_name')
            ->each(function ($website, $id) use (&$current, $total) {
                $current++;
                $this->info("Checking $current/$total: $website->website_name ($website->website_url)");

                try {
                    $response = Http::timeout(intval($this->option('timeout')))
                        ->get($website->website_url);

                    if ($response->failed()) {
                        $this->error("\tError: ".$response->status());
                        $website->inactive = 1;
                    } else {
                        $this->comment("\tOK");
                        $website->inactive = 0;
                    }
                } catch (Exception $e) {
                    $this->error("\tError: ".$e->getMessage());
                    $website->inactive = 1;
                }

                $website->save();
            });

        return 0;
    }
}
