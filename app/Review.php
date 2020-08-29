<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $table = "review_main";
    protected $primaryKey = "review_id";
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class, "user_id");
    }

    public function games()
    {
        return $this->belongsToMany(Game::class, 'review_game', 'review_id', 'game_id');
    }

    public function screenshots()
    {
        return $this->hasMany(ScreenshotReview::class, 'review_id');
    }

    public function score()
    {
        return $this->hasOne(ReviewScore::class, "review_id");
    }
}
