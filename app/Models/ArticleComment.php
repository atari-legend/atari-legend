<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ArticleComment extends Comment
{
    use HasFactory;

    const SECTION = 'Articles';

    protected $table = 'article_comments';

    protected $fillable = [
        'text', 'user_id', 'created_at', 'article_id',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function getTargetAttribute()
    {
        return $this->article->title;
    }

    public function getTargetIdAttribute()
    {
        return $this->article_id;
    }
}
