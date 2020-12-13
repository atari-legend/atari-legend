<?php

namespace App\Models;

use App\Scopes\NonDraftScope;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    const REVIEW_UNPUBLISHED = 1;
    const REVIEW_PUBLISHED = 0;

    protected $table = 'review_main';
    protected $primaryKey = 'review_id';
    public $timestamps = false;

    protected static function booted()
    {
        static::addGlobalScope(new NonDraftScope);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
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
        return $this->hasOne(ReviewScore::class, 'review_id');
    }

    public function comments()
    {
        return $this->belongsToMany(Comment::class, 'review_user_comments', 'review_id', 'comment_id');
    }
}
