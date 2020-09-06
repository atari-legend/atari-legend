<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Release extends Model
{
    protected $table = 'game_release';
    public $timestamps = false;

    public function publisher()
    {
        return $this->belongsTo(PublisherDeveloper::class, 'pub_dev_id');
    }

    public function boxscans()
    {
        return $this->hasMany(ReleaseScan::class, 'game_release_id');
    }
}
