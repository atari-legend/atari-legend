<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;
use Spatie\Feed\Feedable;
use Spatie\Feed\FeedItem;

class Article extends Model implements Feedable
{
    protected $table = 'article_main';
    protected $primaryKey = 'article_id';
    public $timestamps = false;

    protected $fillable = ['user_id', 'article_type_id', 'draft'];

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

    public function toFeedItem(): FeedItem
    {
        return FeedItem::create([
            'id'         => $this->getKey(),
            'title'      => 'Article: ' . $this->article_title,
            'summary'    => Helper::bbCode($this->texts->first()->article_intro),
            'updated'    => $this->texts->first()->article_date,
            'link'       => route('articles.show', $this),
            'authorName' => Helper::user($this->user),
        ]);
    }
}
