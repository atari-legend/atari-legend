<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    protected $table = 'interview_main';
    protected $primaryKey = 'interview_id';
    public $timestamps = false;

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
        return $this->hasMany(ScreenshotInterview::class, 'interview_id');
    }

    public function comments()
    {
        return $this->belongsToMany(GameComment::class, 'interview_user_comments', 'interview_id', 'comment_id');
    }
}
