<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScreenshotInterviewComment extends Model
{
    protected $table = 'interview_comments';
    protected $primaryKey = 'interview_comment_id';
    public $timestamps = false;

    protected $fillable = ['screenshot_interview_id', 'comment_text'];
}
