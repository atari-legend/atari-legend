<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ReviewScreenshot extends Pivot
{
    public $timestamps = false;

    public function comment()
    {
        return $this->hasOne(ReviewScreenshotComment::class, 'review_screenshot_id');
    }
}
