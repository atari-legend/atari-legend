<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameSeries extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];

    public function games()
    {
        return $this->hasMany(Game::class, 'game_series_id');
    }
}
