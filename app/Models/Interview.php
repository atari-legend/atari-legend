<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Feed\Feedable;
use Spatie\Feed\FeedItem;

class Interview extends Model implements Feedable
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'individual_id', 'draft',
        'text', 'published_at', 'intro', 'chapters',
    ];

    protected $casts = [
        'draft'        => 'boolean',
        'published_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function individual()
    {
        return $this->belongsTo(Individual::class);
    }

    public function screenshots()
    {
        return $this->belongsToMany(Screenshot::class)
            // withPivot names columns on the pivot itself: `id` is the pivot's
            // own primary key, so it followed the `id` rename, and
            // `description` is the screenshot's caption.
            ->withPivot('id', 'description')
            ->using(InterviewScreenshot::class);
    }

    public function comments()
    {
        return $this->hasMany(InterviewComment::class);
    }

    /**
     * Get the screenshot pivot for a specific screenshot in this interview.
     */
    public function getScreenshotComment(int $screenshotId)
    {
        return $this->screenshots->firstWhere('id', '=', $screenshotId);
    }

    public function toFeedItem(): FeedItem
    {
        return FeedItem::create([
            'id'         => $this->getKey(),
            'title'      => 'Interview: ' . $this->individual->name,
            'summary'    => Helper::bbCode($this->intro),
            'updated'    => $this->published_at,
            'link'       => route('interviews.show', $this),
            'authorName' => Helper::user($this->user),
        ]);
    }
}
