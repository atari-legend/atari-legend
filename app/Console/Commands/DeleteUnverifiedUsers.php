<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Command to delete accounts that have not been verified for some time
 * after they registered. This is intended to delete bot accounts that register
 * but never verify their email.
 */
class DeleteUnverifiedUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:delete-unverified {--delete : Actually delete the account}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete unverified accounts';

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
        $minDate = Carbon::now()->subDay();

        $this->comment('Deleting unverified accounts older than ' . $minDate->toDateTimeString());

        User::whereNull('email_verified_at')
            ->where('join_date', '<', $minDate->timestamp)
            ->orderBy('join_date')
            ->each(function ($user) {
                $this->comment("Deleting '" . $user->userid . "' " . $user->email . ' (Join date: '
                   . Carbon::createFromTimestamp($user->join_date)->toDateTimeString() . ')');

                if ($this->option('delete')) {
                    $user->delete();
                }
            });

        return 0;
    }
}
