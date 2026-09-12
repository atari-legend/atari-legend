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
        'title', 'text', 'published_at', 'intro',
    ];

    protected $casts = [
        'draft'        => 'boolean',
        'published_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function screenshots()
    {
        return $this->belongsToMany(Screenshot::class)
            // withPivot names columns on the pivot itself: `id` is the pivot's
            // own primary key, so it followed the `id` rename, and
            // `description` is the screenshot's caption.
            ->withPivot('id', 'description')
            ->using(ArticleScreenshot::class);
    }

    public function type()
    {
        return $this->belongsTo(ArticleType::class, 'article_type_id');
    }

    public function comments()
    {
        return $this->hasMany(ArticleComment::class);
    }

    public function toFeedItem(): FeedItem
    {
        return FeedItem::create([
            'id'         => $this->getKey(),
            'title'      => 'Article: ' . $this->title,
            'summary'    => Helper::bbCode($this->intro),
            'updated'    => $this->published_at,
            'link'       => route('articles.show', $this),
            'authorName' => Helper::user($this->user),
        ]);
    }
}
