<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $table = 'article_main';
    protected $primaryKey = 'article_id';
    public $timestamps = false;

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
}
