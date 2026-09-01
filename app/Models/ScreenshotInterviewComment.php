<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScreenshotInterviewComment extends Model
{
    public $timestamps = false;

    protected $fillable = ['screenshot_interview_id', 'text'];
}
