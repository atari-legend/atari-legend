<?php

namespace App\Models;

use Error;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    const TYPE_GAME = 'game';
    const TYPE_REVIEW = 'review';
    const TYPE_INTERVIEW = 'interview';
    const TYPE_ARTICLE = 'article';

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

    /**
     * @return string Get the type of comment, as a comment can apply to different
     *                things on the site.
     */
    public function getTypeAttribute()
    {
        if ($this->games->isNotEmpty()) {
            return self::TYPE_GAME;
        } elseif ($this->articles->isNotEmpty()) {
            return self::TYPE_ARTICLE;
        } elseif ($this->interviews->isNotEmpty()) {
            return self::TYPE_INTERVIEW;
        } elseif ($this->reviews->isNotEmpty()) {
            return self::TYPE_REVIEW;
        } else {
            throw new Error('Unknown comment type');
        }
    }

    /**
     * @return string Name of the target of the comment. For example for a
     *                comment on a game it would be the game name.
     */
    public function getTargetAttribute()
    {
        switch ($this->type) {
            case self::TYPE_GAME:
                return $this->games->first()->game_name;
            case self::TYPE_ARTICLE:
                return $this->articles->first()->texts->first()->article_title;
            case self::TYPE_INTERVIEW:
                return $this->interviews->first()->individual->ind_name;
            case self::TYPE_REVIEW:
                return $this->reviews->first()->games->first()->game_name;
            default:
                throw new Error('Unknown comment type');
        }
    }

    /**
     * @return string ID of the target of the comment. For example for a comment
     *                on a game it would be the game id.
     */
    public function getTargetIdAttribute()
    {
        switch ($this->type) {
            case self::TYPE_GAME:
                return $this->games->first()->getKey();
            case self::TYPE_ARTICLE:
                return $this->articles->first()->getKey();
            case self::TYPE_INTERVIEW:
                return $this->interviews->first()->individual->getKey();
            case self::TYPE_REVIEW:
                return $this->reviews->first()->games->first()->getKey();
            default:
                throw new Error('Unknown comment type');
        }
    }
}
