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

    /**
     * @return string The first nickname of the individual if they have nicknames,
     *                otherwise the individual name
     */
    public function getPublicNickAttribute()
    {
        if ($this->nicknames->isNotEmpty()) {
            return $this->nicknames->first()->nick->ind_name;
        } else {
            return $this->ind_name;
        }
    }

    /**
     * @return string[] List of nicks for this individual
     */
    public function getNickListAttribute()
    {
        return $this->nicknames->pluck('nick')->pluck('ind_name');
    }
}
