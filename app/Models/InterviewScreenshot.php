<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class InterviewScreenshot extends Pivot
{
    public $timestamps = false;

    public function comment()
    {
        return $this->hasOne(InterviewScreenshotComment::class, 'interview_screenshot_id');
    }
}
