<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ScreenshotInterview extends Pivot
{
    public $timestamps = false;

    public function comment()
    {
        return $this->hasOne(ScreenshotInterviewComment::class, 'screenshot_interview_id');
    }
}
