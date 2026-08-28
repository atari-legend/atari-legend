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

        $users = User::whereNull('email_verified_at')
            ->where('join_date', '<', $minDate->timestamp)
            ->orderBy('join_date')
            ->get();

        if ($users->isNotEmpty()) {
            $this->comment('Deleting ' . $users->count() . ' unverified accounts older than '
                . $minDate->toDateTimeString());

            $users->each(function ($user) {
                // A blocked account is skipped and reported rather than
                // attempted. Three foreign keys pointing at users are ON DELETE
                // RESTRICT, and a $user->delete() that hits one throws out of
                // this closure and abandons the rest of the run -- which is
                // unattended, so nobody would see it fail. This is why the
                // guard is an attribute on the model and not a check in the
                // admin controller.
                if (! $user->is_deletable) {
                    $this->warn("Skipping '" . $user->userid . "' " . $user->email
                        . ': still holds a game submission or a dump');

                    return;
                }

                $this->comment("Deleting '" . $user->userid . "' " . $user->email . ' (Join date: '
                    . Carbon::createFromTimestamp($user->join_date)->toDateTimeString() . ')');

                if ($this->option('delete')) {
                    $user->delete();
                }
            });
        }

        return 0;
    }
}
