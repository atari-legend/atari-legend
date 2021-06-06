<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ScreenshotInterview extends Pivot
{
    protected $table = 'screenshot_interview';
    protected $primaryKey = 'screenshot_interview_id';
    public $timestamps = false;

    public function comment()
    {
        return $this->hasOne(ScreenshotInterviewComment::class, 'screenshot_interview_id');
    }
}
