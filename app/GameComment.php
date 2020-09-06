<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GameComment extends Model
{
    protected $table = 'comments';
    protected $primaryKey = 'comments_id';
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function games()
    {
        // FIXME: Should be N:1
        return $this->belongsToMany(Game::class, 'game_user_comments', 'comment_id', 'game_id');
    }
}
