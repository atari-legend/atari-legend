<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class GameComment extends Comment
{
    use HasFactory;

    const SECTION = 'Games';

    protected $table = 'game_comments';

    protected $fillable = [
        'text', 'user_id', 'created_at', 'game_id',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function getTargetAttribute()
    {
        return $this->game->name;
    }

    public function getTargetIdAttribute()
    {
        return $this->game_id;
    }
}
