<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Crew extends Model
{
    protected $table = 'crew';
    protected $primaryKey = 'crew_id';
    public $timestamps = false;

    protected $fillable = ['crew_name'];

    public function menuSets()
    {
        return $this->belongsToMany(MenuSet::class, null, 'crew_id');
    }

    public function individuals()
    {
        return $this->belongsToMany(Individual::class, 'crew_individual', 'crew_id', 'ind_id');
    }
}
