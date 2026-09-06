<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameFact extends Model
{
    protected $fillable = ['fact'];
    public $timestamps = false;

    public function screenshots()
    {
        return $this->belongsToMany(Screenshot::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }
}
