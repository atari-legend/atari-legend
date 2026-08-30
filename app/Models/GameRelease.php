<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GameRelease extends Model
{
    use HasFactory;

    const LICENCE_COMMERCIAL = 'Commercial';
    const LICENSE_NON_COMMERCIAL = 'Non-Commercial';
    const LICENSES = [
        GameRelease::LICENCE_COMMERCIAL,
        GameRelease::LICENSE_NON_COMMERCIAL,
    ];

    const TYPE_UNOFFICIAL = 'Unofficial';
    const TYPES = [
        'Re-release', 'Budget', 'Budget re-release',
        'Playable demo', 'Non-playable demo', 'Slideshow',
        GameRelease::TYPE_UNOFFICIAL, 'Data disk', 'Review copy',
    ];

    const STATUSES = ['Unfinished', 'Development', 'Unreleased'];

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
        return $this->belongsTo(Game::class);
    }

    public function publisher()
    {
        return $this->belongsTo(PubDev::class, 'pub_dev_id');
    }

    public function crews()
    {
        return $this->belongsToMany(Crew::class, 'game_release_crew');
    }

    public function boxscans()
    {
        return $this->hasMany(GameReleaseScan::class);
    }

    public function distributors()
    {
        return $this->belongsToMany(PubDev::class, 'game_release_distributor');
    }

    public function locations()
    {
        return $this->belongsToMany(Location::class, 'game_release_location');
    }

    public function resolutions()
    {
        return $this->belongsToMany(Resolution::class, 'game_release_resolution');
    }

    public function systemEnhanced()
    {
        return $this->hasMany(GameReleaseSystemEnhanced::class);
    }

    public function systemIncompatibles()
    {
        return $this->belongsToMany(System::class, 'game_release_system_incompatible');
    }

    public function memoryEnhanced()
    {
        return $this->hasMany(GameReleaseMemoryEnhanced::class);
    }

    public function memoryMinimums()
    {
        return $this->belongsToMany(Memory::class, 'game_release_memory_minimum');
    }

    public function memoryIncompatibles()
    {
        return $this->belongsToMany(Memory::class, 'game_release_memory_incompatible');
    }

    public function emulatorIncompatibles()
    {
        return $this->belongsToMany(Emulator::class, 'game_release_emulator_incompatibility');
    }

    public function tosIncompatibles()
    {
        return $this->hasMany(GameReleaseTosVersionIncompatibility::class);
    }

    public function akas()
    {
        return $this->hasMany(GameReleaseAka::class);
    }

    public function trainers()
    {
        return $this->belongsToMany(TrainerOption::class, 'game_release_trainer_option');
    }

    public function copyProtections()
    {
        return $this
            ->belongsToMany(CopyProtection::class, 'game_release_copy_protection')
            ->withPivot('notes');
    }

    public function diskProtections()
    {
        return $this
            ->belongsToMany(DiskProtection::class, 'game_release_disk_protection')
            ->withPivot('notes');
    }

    public function languages()
    {
        return $this->belongsToMany(Language::class, 'game_release_language');
    }

    public function medias()
    {
        return $this->hasMany(Media::class);
    }

    public function menuDiskContents()
    {
        return $this->hasMany(MenuDiskContent::class);
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
