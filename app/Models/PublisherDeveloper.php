<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublisherDeveloper extends Model
{
    protected $table = 'pub_dev';
    protected $primaryKey = 'pub_dev_id';
    public $timestamps = false;

    protected $fillable = ['pub_dev_name'];

    public function text()
    {
        // FIXME: The DB structure actually allows many
        return $this->hasOne(PublisherDeveloperText::class, 'pub_dev_id');
    }

    public function games()
    {
        return $this->belongsToMany(Game::class, 'game_developer', 'dev_pub_id', 'game_id');
    }

    public function releases()
    {
        return $this->hasMany(Release::class, 'pub_dev_id');
    }

    public function getLogoAttribute()
    {
        if ($this->text?->file) {
            return asset("storage/images/company_logos/{$this->text->file}");
        } else {
            return null;
        }
    }
}
