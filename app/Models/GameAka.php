<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameAka extends Model
{
    protected $table = 'game_aka';
    protected $primaryKey = 'game_aka_id';
    public $timestamps = false;

    public function language()
    {
        return $this->belongsTo(Language::class);
    }
}
