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

    protected $table = 'interview_main';
    public $timestamps = false;

    protected $fillable = ['user_id', 'ind_id', 'draft'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function individual()
    {
        return $this->belongsTo(Individual::class, 'ind_id');
    }

    public function texts()
    {
        return $this->hasMany(InterviewText::class, 'interview_id');
    }

    public function screenshots()
    {
        return $this->belongsToMany(Screenshot::class, 'screenshot_interview', 'interview_id', 'screenshot_id')
            // withPivot names a column on the pivot itself, and this is the
            // pivot's own key, so it follows the rename. The interview_id and
            // screenshot_id arguments above are foreign keys and do not.
            ->withPivot('id')
            ->using(ScreenshotInterview::class);
    }

    public function comments()
    {
        return $this->belongsToMany(Comment::class, 'interview_user_comments', 'interview_id', 'comment_id');
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
            'title'      => 'Interview: ' . $this->individual->ind_name,
            'summary'    => Helper::bbCode($this->texts->first()->interview_intro),
            'updated'    => $this->texts->first()->interview_date,
            'link'       => route('interviews.show', $this),
            'authorName' => Helper::user($this->user),
        ]);
    }
}
