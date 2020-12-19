<?php

namespace App\Providers;

use App\Models\Changelog;
use App\Models\ChangeLogable;
use App\Models\User;
use ErrorException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

/**
 * Populates the change_log table whenever Models are created, updated
 * or deleted.
 */
class ChangeLogServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Event::listen(['eloquent.created: *'], function ($event, $data) {
            $this->processModels('Insert', $data);
        });
        Event::listen(['eloquent.updated: *'], function ($event, $data) {
            $this->processModels('Update', $data);
        });
        Event::listen(['eloquent.deleted: *'], function ($event, $data) {
            $this->processModels('Delete', $data);
        });
    }

    /**
     * Process models to update the change_log accordingly.
     *
     * @param string $action Changelog action
     * @param array  $models List of models to process
     */
    private function processModels($action, $models)
    {
        collect($models)
            ->filter(function ($model) {
                return $model instanceof ChangeLogable;
            })
            ->each(function ($model) use ($action) {
                $changelogData = $model->getChangelogData();

                // Check if all keys are present in the data
                $missingKeys = $this->getMissingKeys($changelogData);
                if (count($missingKeys) > 0) {
                    throw new ErrorException('Missing changelog key(s) \''
                        .join(', ', $missingKeys)
                        .'\' in '.get_class($model)
                        .'. Check its getChangelogData() function');
                }

                $log = Changelog::create(
                    array_merge($changelogData, [
                        'user_id'          => Auth::user()->user_id ?? -1,   // No user available during registration
                        'action'           => $action,
                        'timestamp'        => time(),
                    ])
                );

                // Special case for user registration: There's no authenticated user
                // yet, so assign the ID of the new user being created
                if ($model instanceof User && !Auth::check()) {
                    $log->user_id = $model->user_id;
                }

                Log::debug("Saving changelog: {$log}");
                $log->save();
            });
    }

    /**
     * Find missing keys in the changelog data.
     *
     * @param array $data Changelog data
     *
     * @return array Missing keys, or an empty array if no keys are missing
     */
    private function getMissingKeys(array $data): array
    {
        $keys = array_keys($data);

        return collect(ChangeLogable::CHANGELOG_KEYS)
            ->reject(function ($item) use ($keys) {
                return in_array($item, $keys);
            })
            ->all();
    }
}
