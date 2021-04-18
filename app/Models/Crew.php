<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;

class Crew extends Model
{
    protected $table = 'crew';
    protected $primaryKey = 'crew_id';
    public $timestamps = false;

    protected $fillable = ['crew_name', 'crew_history'];

    public function getLogoFileAttribute()
    {
        return Helper::filename($this->getKey(), $this->crew_logo);
    }

    public function menuSets()
    {
        return $this->belongsToMany(MenuSet::class, null, 'crew_id');
    }

    public function releases()
    {
        return $this->belongsToMany(Release::class, 'game_release_crew', 'crew_id', 'game_release_id');
    }

    public function individuals()
    {
        return $this->belongsToMany(Individual::class, 'crew_individual', 'crew_id', 'ind_id');
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
