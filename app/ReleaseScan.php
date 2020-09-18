<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReleaseScan extends Model
{
    protected $table = 'game_release_scan';
    public $timestamps = false;

    public function getFileAttribute()
    {
        return $this->id
            .'.'
            .$this->imgext;
    }
}
