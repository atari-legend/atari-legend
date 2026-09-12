<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Feed\Feedable;
use Spatie\Feed\FeedItem;

class Review extends Model implements Feedable
{
    use HasFactory;

    const REVIEW_UNPUBLISHED = 1;
    const REVIEW_PUBLISHED = 0;

    public $timestamps = false;

    protected $fillable = [
        'game_id', 'user_id', 'draft', 'text', 'published_at', 'submission',
        'graphics', 'sound', 'gameplay', 'overall',
    ];

    protected $casts = [
        'draft'        => 'boolean',
        'submission'   => 'boolean',
        'published_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function screenshots()
    {
        return $this->belongsToMany(Screenshot::class)
            // withPivot names columns on the pivot itself: `id` is the pivot's
            // own primary key, so it followed the `id` rename, and
            // `description` is the screenshot's caption.
            ->withPivot('id', 'description')
            ->using(ReviewScreenshot::class);
    }

    /**
     * Get the screenshot pivot for a specific screenshot in this review,
     * which is what carries its caption.
     *
     * @param  int  $screenshotId  ID of the screenshot to get the caption for
     * @return Screenshot|null The screenshot with its pivot loaded, or null if not found
     */
    public function getScreenshotComment(int $screenshotId)
    {
        return $this->screenshots->firstWhere('id', '=', $screenshotId);
    }

    public function comments()
    {
        return $this->hasMany(ReviewComment::class);
    }

    public function toFeedItem(): FeedItem
    {
        return FeedItem::create([
            'id'         => $this->getKey(),
            'title'      => 'Review: ' . $this->game->name,
            'summary'    => Helper::bbCode(Helper::extractTag(e($this->text), 'frontpage')),
            'updated'    => $this->published_at,
            'link'       => route('reviews.show', $this),
            'authorName' => Helper::user($this->user),
        ]);
    }
}
