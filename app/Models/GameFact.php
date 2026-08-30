<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameFact extends Model
{
    protected $fillable = ['game_fact'];
    public $timestamps = false;

    public function screenshots()
    {
        return $this->belongsToMany(Screenshot::class, 'screenshot_game_fact');
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }
}
