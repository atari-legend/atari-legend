<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;
use Spatie\Feed\Feedable;
use Spatie\Feed\FeedItem;

class Review extends Model implements Feedable
{
    const REVIEW_UNPUBLISHED = 1;
    const REVIEW_PUBLISHED = 0;

    protected $table = 'review_main';
    protected $primaryKey = 'review_id';
    public $timestamps = false;

    protected $fillable = ['user_id', 'draft', 'review_text', 'review_date', 'review_edit'];

    protected $casts = [
        'review_date' => 'datetime:timestamp',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function games()
    {
        return $this->belongsToMany(Game::class, 'review_game', 'review_id', 'game_id');
    }

    public function screenshots()
    {
        return $this->belongsToMany(Screenshot::class, 'screenshot_review', 'review_id', 'screenshot_id')
            ->withPivot('screenshot_review_id')
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
        return $this->screenshots->firstWhere('screenshot_id', '=', $screenshotId);
    }

    public function score()
    {
        return $this->hasOne(ReviewScore::class, 'review_id');
    }

    public function comments()
    {
        return $this->belongsToMany(Comment::class, 'review_user_comments', 'review_id', 'comment_id');
    }

    public function toFeedItem(): FeedItem
    {
        return FeedItem::create([
            'id'         => $this->getKey(),
            'title'      => 'Review: ' . $this->games->first()->game_name,
            'summary'    => Helper::bbCode(Helper::extractTag(e($this->review_text), 'frontpage')),
            'updated'    => $this->review_date,
            'link'       => route('reviews.show', $this),
            'authorName' => Helper::user($this->user),
        ]);
    }
}
