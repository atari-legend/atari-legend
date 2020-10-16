<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScreenshotReview extends Model
{
    protected $table = 'screenshot_review';
    protected $primaryKey = 'screenshot_review_id';
    public $timestamps = false;

    public function screenshot()
    {
        return $this->belongsTo(Screenshot::class, 'screenshot_id');
    }

    public function comment()
    {
        return $this->hasOne(ScreenshotReviewComment::class, 'screenshot_review_id');
    }
}
