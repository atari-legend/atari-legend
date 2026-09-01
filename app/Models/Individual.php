<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Individual extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['name', 'profile', 'imgext', 'email'];

    public function games()
    {
        return $this->belongsToMany(Game::class, 'game_individual')
            ->withPivot('individual_role_id')
            ->using(GameIndividual::class);
    }

    public function interviews()
    {
        return $this->hasMany(Interview::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany Nicknames of the individuals.
     *                                                               This is a self-reference.
     */
    public function nicknames()
    {
        return $this->belongsToMany(Individual::class, 'individual_nicks', 'individual_id', 'nick_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany Individuals of the nickname.
     *                                                               This is a self-reference.
     */
    public function individuals()
    {
        return $this->belongsToMany(Individual::class, 'individual_nicks', 'nick_id', 'individual_id');
    }

    public function crews()
    {
        return $this->belongsToMany(Crew::class, 'crew_individual');
    }

    public function getFileAttribute()
    {
        return Helper::filename($this->getKey(), $this->imgext);
    }

    public function getImageUrlAttribute()
    {
        return asset('storage/' . $this->path);
    }

    public function getPathAttribute()
    {
        return 'images/individual_screenshots/' . $this->file;
    }

    public function getAvatarAttribute()
    {
        if ($this->file) {
            return asset('storage/' . $this->path);
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
            return $this->nicknames->first()->name;
        } else {
            return $this->name;
        }
    }

    /**
     * @return string[] List of nicks for this individual
     */
    public function getNickListAttribute()
    {
        return $this->nicknames->pluck('name');
    }

    /**
     * @return string[] List of individuals for this nick
     */
    public function getIndividualListAttribute()
    {
        return $this->individuals->pluck('name');
    }

    public function getAkaListAttribute()
    {
        return $this->nick_list->concat($this->individual_list);
    }
}
