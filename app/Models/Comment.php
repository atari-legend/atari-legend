<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
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

    public function articles()
    {
        // FIXME: Should be N:1
        return $this->belongstoMany(Article::class, 'article_user_comments', 'comments_id', 'article_id');
    }

    public function interviews()
    {
        // FIXME: Should be N:1
        return $this->belongstoMany(Interview::class, 'interview_user_comments', 'comment_id', 'interview_id');
    }

    public function reviews()
    {
        // FIXME: Should be N:1
        return $this->belongstoMany(Review::class, 'review_user_comments', 'comment_id', 'review_id');
    }
}
