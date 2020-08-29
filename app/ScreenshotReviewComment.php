<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ScreenshotReviewComment extends Model
{
    protected $table = "review_comments";
    protected $primaryKey = "review_comments_id";
    public $timestamps = false;
}
