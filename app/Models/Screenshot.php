<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;

class Screenshot extends Model
{
    protected $table = 'screenshot_main';
    protected $primaryKey = 'screenshot_id';
    public $timestamps = false;

    public function getFileAttribute()
    {
        return Helper::filename($this->screenshot_id, $this->imgext);
    }

    public function reviewScreenshots()
    {
        return $this->hasMany(ScreenshotReview::class, 'screenshot_review_id');
    }
}
