<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ScreenshotReview extends Pivot
{
    protected $table = 'screenshot_review';
    protected $primaryKey = 'screenshot_review_id';
    public $timestamps = false;

    public function comment()
    {
        return $this->hasOne(ScreenshotReviewComment::class, 'screenshot_review_id');
    }
}
