<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Release extends Model
{
    const LICENCE_COMMERCIAL = 'Commercial';
    const LICENSE_NON_COMMERCIAL = 'Non-Commercial';
    const LICENSES = [
        Release::LICENCE_COMMERCIAL,
        Release::LICENSE_NON_COMMERCIAL,
    ];

    const TYPE_UNOFFICIAL = 'Unofficial';
    const TYPES = [
        'Re-release', 'Budget', 'Budget re-release',
        'Playable demo', 'Non-playable demo', 'Slideshow',
        Release::TYPE_UNOFFICIAL, 'Data disk', 'Review copy',
    ];

    const STATUSES = ['Unfinished', 'Development', 'Unreleased'];

    protected $table = 'game_release';
    public $timestamps = false;

    protected $casts = [
        'date'           => 'date',
        'hd_installable' => 'boolean',
    ];

    protected $fillable = ['type', 'game_id', 'date', 'license', 'status', 'name', 'notes', 'hd_installable'];

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
     * @return Illuminate\Database\Eloquent\Collection All dumps for this release, across all media
     */
    public function getDumpsAttribute()
    {
        return $this->medias
            ->flatMap(function ($media) {
                return $media->dumps;
            });
    }

    /**
     * @return bool true if the release has goodies scans, false otherwise
     */
    public function getHasGoodiesAttribute()
    {
        return $this->boxscans
            ->contains(function ($boxscan) {
                return ! Str::startsWith($boxscan->type, 'Box');
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

    public function crews()
    {
        return $this->belongsToMany(Crew::class, 'game_release_crew', 'game_release_id', 'crew_id');
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

    public function menuDiskContents()
    {
        return $this->hasMany(MenuDiskContent::class, 'game_release_id');
    }

    public function getMenuAttribute(): ?string
    {
        if ($this->menuDiskContents->isNotEmpty()) {
            return collect([
                $this->menuDiskContents->first()->menuDisk->menu->full_label,
                $this->menuDiskContents->first()->menuDisk->label,
            ])->join(' ');
        } else {
            return null;
        }
    }

    public function getFullLabelAttribute(): string
    {
        $label = $this->year;
        if ($this->name) {
            $label .= ' as ' . $this->name;
        }
        if ($this->publisher) {
            $label .= ' by ' . $this->publisher->pub_dev_name;
        }
        if ($this->menu) {
            $label .= ' on ' . $this->menu;
        }
        if ($this->locations->isNotEmpty()) {
            $label .= ' in ' . $this->locations->pluck('name')->join(', ');
        }

        return $label;
    }
}
