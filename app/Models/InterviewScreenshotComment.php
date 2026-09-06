<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewScreenshotComment extends Model
{
    public $timestamps = false;

    protected $fillable = ['interview_screenshot_id', 'text'];
}
