<?php

namespace App\Models;

use App\Scopes\NonDraftScope;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $table = 'article_main';
    protected $primaryKey = 'article_id';
    public $timestamps = false;

    protected static function booted()
    {
        static::addGlobalScope(new NonDraftScope());
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function texts()
    {
        return $this->hasMany(ArticleText::class, 'article_id');
    }

    public function screenshots()
    {
        return $this->belongsToMany(Screenshot::class, 'screenshot_article', 'article_id', 'screenshot_id')
            ->withPivot('screenshot_article_id')
            ->using(ScreenshotArticle::class);
    }

    public function type()
    {
        return $this->belongsTo(ArticleType::class, 'article_type_id');
    }

    public function comments()
    {
        return $this->belongsToMany(Comment::class, 'article_user_comments', 'article_id', 'comments_id');
    }
}
