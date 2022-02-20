<?php

namespace App\Models;

use App\Helpers\Helper;
use App\Scopes\NonDraftScope;
use Illuminate\Database\Eloquent\Model;
use Spatie\Feed\Feedable;
use Spatie\Feed\FeedItem;

class Interview extends Model implements Feedable
{
    protected $table = 'interview_main';
    protected $primaryKey = 'interview_id';
    public $timestamps = false;

    protected static function booted()
    {
        static::addGlobalScope(new NonDraftScope());
    }

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
            ->withPivot('screenshot_interview_id')
            ->using(ScreenshotInterview::class);
    }

    public function comments()
    {
        return $this->belongsToMany(Comment::class, 'interview_user_comments', 'interview_id', 'comment_id');
    }

    public function toFeedItem(): FeedItem
    {
        return FeedItem::create([
            'id'         => $this->getKey(),
            'title'      => 'Interview: '.$this->individual->ind_name,
            'summary'    => Helper::bbCode($this->texts->first()->interview_intro),
            'updated'    => $this->texts->first()->interview_date,
            'link'       => route('interviews.show', $this),
            'authorName' => Helper::user($this->user),
        ]);
    }
}
