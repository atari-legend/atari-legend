<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameFact extends Model
{
    protected $table = 'game_fact';
    protected $primaryKey = 'game_fact_id';
    protected $fillable = ['game_fact'];
    public $timestamps = false;

    public function screenshots()
    {
        return $this->belongsToMany(Screenshot::class, 'screenshot_game_fact', 'game_fact_id', 'screenshot_id');
    }

    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }
}
