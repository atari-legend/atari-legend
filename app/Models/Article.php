<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Feed\Feedable;
use Spatie\Feed\FeedItem;

class Article extends Model implements Feedable
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'article_type_id', 'draft',
        'title', 'text', 'date', 'intro',
    ];

    protected $casts = [
        'date' => 'datetime:timestamp',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function screenshots()
    {
        return $this->belongsToMany(Screenshot::class, 'screenshot_article')
            // withPivot names a column on the pivot itself, and this is the
            // pivot's own primary key, so it followed the `id` rename.
            ->withPivot('id')
            ->using(ScreenshotArticle::class);
    }

    public function type()
    {
        return $this->belongsTo(ArticleType::class, 'article_type_id');
    }

    public function comments()
    {
        return $this->belongsToMany(Comment::class, 'article_user_comments');
    }

    public function toFeedItem(): FeedItem
    {
        return FeedItem::create([
            'id'         => $this->getKey(),
            'title'      => 'Article: ' . $this->title,
            'summary'    => Helper::bbCode($this->intro),
            'updated'    => $this->date,
            'link'       => route('articles.show', $this),
            'authorName' => Helper::user($this->user),
        ]);
    }
}
