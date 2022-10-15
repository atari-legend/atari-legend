<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameAka extends Model
{
    protected $table = 'game_aka';
    protected $primaryKey = 'game_aka_id';
    public $timestamps = false;

    protected $fillable = ['game_id', 'aka_name', 'language_id'];

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    public function game()
    {
        return $this->hasOne(Game::class, 'game_id', 'game_id');
    }
}
