<?php

namespace App\Providers;

use App\Models\Changelog;
use App\Models\ChangeLogable;
use App\Models\User;
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
                $log = Changelog::create([
                    'section'          => $model->getSection(),
                    'section_id'       => $model->getSectionId(),
                    'section_name'     => $model->getSectionName(),
                    'sub_section'      => $model->getSubSection(),
                    'sub_section_id'   => $model->getSubSectionId(),
                    'sub_section_name' => $model->getSubSectionName(),
                    'user_id'          => Auth::user()->user_id ?? -1,   // No user available during registration
                    'action'           => $action,
                    'timestamp'        => time(),
                ]);

                // Special case for user registration: There's no authenticated user
                // yet, so assign the ID of the new user being created
                if ($model instanceof User && !Auth::check()) {
                    $log->user_id = $model->user_id;
                }

                Log::debug("Saving changelog: {$log}");
                $log->save();
            });
    }
}
