<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReleaseAka extends Model
{
    protected $table = 'game_release_aka';
    public $timestamps = false;

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
