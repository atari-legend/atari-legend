<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $table = 'article_main';
    protected $primaryKey = 'article_id';
    public $timestamps = false;

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
        return $this->hasMany(ScreenshotArticle::class, 'article_id');
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
