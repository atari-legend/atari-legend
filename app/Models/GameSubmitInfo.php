<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameSubmitInfo extends Model
{
    const SUBMISSION_NEW = '2';
    const SUBMISSION_REVIEWED = '1';

    protected $table = 'game_submitinfo';
    protected $primaryKey = 'game_submitinfo_id';
    public $timestamps = false;

    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    public function screenshots()
    {
        return $this->belongsToMany(Screenshot::class, 'screenshot_game_submitinfo', 'game_submitinfo_id', 'screenshot_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
