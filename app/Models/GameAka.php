<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameAka extends Model
{
    public $timestamps = false;

    protected $fillable = ['game_id', 'name', 'language_id'];

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }
}
