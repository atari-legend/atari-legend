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
        'user_id', 'draft', 'text', 'date', 'edit',
        'graphics', 'sound', 'gameplay', 'overall',
    ];

    protected $casts = [
        'date' => 'datetime:timestamp',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function games()
    {
        return $this->belongsToMany(Game::class, 'review_game');
    }

    public function screenshots()
    {
        return $this->belongsToMany(Screenshot::class, 'screenshot_review')
            // withPivot names a column on the pivot itself, and this is the
            // pivot's own primary key, so it followed the `id` rename.
            ->withPivot('id')
            ->using(ScreenshotReview::class);
    }

    /**
     * Get the comment for a specific screenshot in this review.
     *
     * @param  int  $screenshotId  ID of the screenshot to get the comment for
     * @return ScreenshotReview|null The ScreenshotReview pivot model with the comment, or null if not found
     */
    public function getScreenshotComment(int $screenshotId)
    {
        return $this->screenshots->firstWhere('id', '=', $screenshotId);
    }

    public function comments()
    {
        return $this->belongsToMany(Comment::class, 'review_user_comments');
    }

    public function toFeedItem(): FeedItem
    {
        return FeedItem::create([
            'id'         => $this->getKey(),
            'title'      => 'Review: ' . $this->games->first()->name,
            'summary'    => Helper::bbCode(Helper::extractTag(e($this->text), 'frontpage')),
            'updated'    => $this->date,
            'link'       => route('reviews.show', $this),
            'authorName' => Helper::user($this->user),
        ]);
    }
}
