<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ScreenshotInterview extends Model
{
    protected $table = 'screenshot_interview';
    protected $primaryKey = 'screenshot_interview_id';
    public $timestamps = false;

    public function screenshot()
    {
        return $this->belongsTo(Screenshot::class, 'screenshot_id');
    }

    public function comment()
    {
        return $this->hasOne(ScreenshotInterviewComment::class, 'screenshot_interview_id');
    }
}
