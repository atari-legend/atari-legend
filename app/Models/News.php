<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;
use Spatie\Feed\Feedable;
use Spatie\Feed\FeedItem;

class News extends Model implements Feedable
{
    protected $primaryKey = 'news_id';
    public $timestamps = false;

    protected $fillable = [
        'news_headline', 'user_id', 'news_date', 'news_text',
    ];

    protected $casts = [
        'news_date' => 'datetime:timestamp',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function image()
    {
        return $this->belongsTo(NewsImage::class, 'news_image_id');
    }

    public function getNewsImageAttribute()
    {
        if ($this->image?->file) {
            return asset('storage/images/news_images/'.$this->image->file);
        } else {
            return null;
        }
    }

    public function toFeedItem(): FeedItem
    {
        return FeedItem::create([
            'id'         => $this->getKey(),
            'title'      => $this->news_headline,
            'summary'    => Helper::bbCode(Helper::extractTag(e($this->news_text), 'frontpage')),
            'updated'    => $this->news_date,
            // Use an ID so that articles in the feed have different IDs
            // The ID is effectively ignored in the News page
            'link'       => route('news.index', ['news' => $this->news_id]),
            'authorName' => Helper::user($this->user),
        ]);
    }
}
