<?php

namespace App\Models;

use App\Scopes\NonDraftScope;
use Illuminate\Database\Eloquent\Model;

class Interview extends Model
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
}
