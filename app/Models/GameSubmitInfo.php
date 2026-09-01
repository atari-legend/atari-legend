<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameSubmitInfo extends Model
{
    const SUBMISSION_NEW = '2';
    const SUBMISSION_REVIEWED = '1';

    public $timestamps = false;

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function screenshots()
    {
        return $this->belongsToMany(Screenshot::class, 'screenshot_game_submitinfo');
    }

    public function user()
    {
        // No third argument: the owner key on User is now `id`, the default.
        return $this->belongsTo(User::class);
    }
}
