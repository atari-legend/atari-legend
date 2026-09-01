<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Feed\Feedable;
use Spatie\Feed\FeedItem;

class News extends Model implements Feedable
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'headline', 'user_id', 'date', 'text',
    ];

    protected $casts = [
        'date' => 'datetime:timestamp',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function image()
    {
        return $this->belongsTo(NewsImage::class, 'news_image_id');
    }

    public function getNewsImageAttribute()
    {
        if ($this->image?->file) {
            return asset('storage/images/news_images/' . $this->image->file);
        } else {
            return null;
        }
    }

    public function toFeedItem(): FeedItem
    {
        return FeedItem::create([
            'id'         => $this->getKey(),
            'title'      => $this->headline,
            'summary'    => Helper::bbCode(Helper::extractTag(e($this->text), 'frontpage')),
            'updated'    => $this->date,
            // Use an ID so that articles in the feed have different IDs
            // The ID is effectively ignored in the News page
            'link'       => route('news.index', ['news' => $this->getKey()]),
            'authorName' => Helper::user($this->user),
        ]);
    }
}
