<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Release extends Model
{
    protected $table = 'game_release';
    public $timestamps = false;

    protected $casts = [
        'date'           => 'date',
        'hd_installable' => 'boolean',
    ];

    /**
     * @return string Year of a release, or '[no date] if the release has no date.
     */
    public function getYearAttribute()
    {
        if ($this->date !== null) {
            return $this->date->year;
        } else {
            return '[no date]';
        }
    }

    /**
     * @return array[] All dumps for this release, across all media
     */
    public function getDumpsAttribute()
    {
        return $this->medias
            ->flatMap(function ($media) {
                return $media->dumps;
            });
    }

    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    public function publisher()
    {
        return $this->belongsTo(PublisherDeveloper::class, 'pub_dev_id');
    }

    public function boxscans()
    {
        return $this->hasMany(ReleaseScan::class, 'game_release_id');
    }

    public function distributors()
    {
        return $this->belongsToMany(PublisherDeveloper::class, 'game_release_distributor', 'game_release_id', 'pub_dev_id');
    }

    public function locations()
    {
        return $this->belongsToMany(Location::class, 'game_release_location', 'game_release_id', 'location_id');
    }

    public function resolutions()
    {
        return $this->belongsToMany(Resolution::class, 'game_release_resolution', 'game_release_id', 'resolution_id');
    }

    public function systemEnhanced()
    {
        return $this->hasMany(ReleaseSystemEnhanced::class, 'game_release_id');
    }

    public function systemIncompatibles()
    {
        return $this->belongsToMany(System::class, 'game_release_system_incompatible', 'game_release_id', 'system_id');
    }

    public function memoryEnhanced()
    {
        return $this->hasMany(ReleaseMemoryEnhanced::class, 'release_id');
    }

    public function memoryMinimums()
    {
        return $this->belongsToMany(Memory::class, 'game_release_memory_minimum', 'release_id', 'memory_id');
    }

    public function memoryIncompatibles()
    {
        return $this->belongsToMany(Memory::class, 'game_release_memory_incompatible', 'release_id', 'memory_id');
    }

    public function emulatorIncompatibles()
    {
        return $this->belongsToMany(Emulator::class, 'game_release_emulator_incompatibility', 'release_id', 'emulator_id');
    }

    public function tosIncompatibles()
    {
        return $this->hasMany(ReleaseTOSIncompatibility::class, 'release_id');
    }

    public function akas()
    {
        return $this->hasMany(ReleaseAka::class, 'game_release_id');
    }

    public function trainers()
    {
        return $this->belongsToMany(Trainer::class, 'game_release_trainer_option', 'release_id', 'trainer_option_id');
    }

    public function copyProtections()
    {
        return $this
            ->belongsToMany(CopyProtection::class, 'game_release_copy_protection', 'release_id', 'copy_protection_id')
            ->withPivot('notes');
    }

    public function diskProtections()
    {
        return $this
            ->belongsToMany(DiskProtection::class, 'game_release_disk_protection', 'release_id', 'disk_protection_id')
            ->withPivot('notes');
    }

    public function languages()
    {
        return $this->belongsToMany(Language::class, 'game_release_language', 'release_id', 'language_id');
    }

    public function medias()
    {
        return $this->hasMany(Media::class);
    }
}
