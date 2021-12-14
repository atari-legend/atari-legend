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
        return $this->belongsToMany(Game::class, 'game_individual', 'individual_id', 'game_id')
            ->withPivot('individual_role_id')
            ->using(GameIndividual::class);
    }

    public function interviews()
    {
        return $this->hasMany(Interview::class, 'ind_id');
    }

    /**
     * @return \App\Models\Individual[] Nicknames of the individuals.
     *                                  This is a self-reference.
     */
    public function nicknames()
    {
        return $this->belongsToMany(Individual::class, 'individual_nicks', 'ind_id', 'nick_id');
    }

    /**
     * @return \App\Models\Individual[] Individuals of the nickname.
     *                                  This is a self-reference.
     */
    public function individuals()
    {
        return $this->belongstoMany(Individual::class, 'individual_nicks', 'nick_id', 'ind_id');
    }

    public function crews()
    {
        return $this->belongsToMany(Crew::class, 'crew_individual', 'ind_id', 'crew_id');
    }

    public function getAvatarAttribute()
    {
        if ($this->text?->file) {
            return asset('storage/images/individual_screenshots/'.$this->text->file);
        } else {
            return null;
        }
    }

    /**
     * @return string The first nickname of the individual if they have nicknames,
     *                otherwise the individual name
     */
    public function getPublicNickAttribute()
    {
        if ($this->nicknames->isNotEmpty()) {
            return $this->nicknames->first()->ind_name;
        } else {
            return $this->ind_name;
        }
    }

    /**
     * @return string[] List of nicks for this individual
     */
    public function getNickListAttribute()
    {
        return $this->nicknames->pluck('ind_name');
    }

    /**
     * @return string[] List of individuals for this nick
     */
    public function getIndividualListAttribute()
    {
        return $this->individuals->pluck('ind_name');
    }

    public function getAkaListAttribute()
    {
        return $this->nick_list->concat($this->individual_list);
    }
}
