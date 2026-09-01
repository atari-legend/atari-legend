<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Crew extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['name', 'history'];

    public function getLogoFileAttribute()
    {
        return Helper::filename($this->getKey(), $this->logo);
    }

    public function menuSets()
    {
        return $this->belongsToMany(MenuSet::class);
    }

    public function releases()
    {
        return $this->belongsToMany(GameRelease::class, 'game_release_crew');
    }

    public function individuals()
    {
        return $this->belongsToMany(Individual::class, 'crew_individual');
    }

    public function parentCrews()
    {
        return $this->belongsToMany(Crew::class, 'sub_crew', 'crew_id', 'parent_id');
    }

    public function subCrews()
    {
        return $this->belongsToMany(Crew::class, 'sub_crew', 'parent_id', 'crew_id');
    }
}
