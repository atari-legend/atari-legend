<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Individual extends Model
{
    protected $table = 'individuals';
    protected $primaryKey = 'ind_id';
    public $timestamps = false;

    public function text()
    {
        // FIXME: The DB structure actually allows many
        return $this->hasOne(IndividualText::class, 'ind_id');
    }

    public function games()
    {
        return $this->hasMany(GameIndividual::class, 'individual_id');
    }

    public function interviews()
    {
        return $this->hasMany(Interview::class, 'ind_id');
    }

    public function nicknames()
    {
        return $this->hasMany(IndividualNick::class, 'ind_id');
    }

    public function crews()
    {
        return $this->belongsToMany(Crew::class, 'crew_individual', 'ind_id', 'crew_id');
    }
}
