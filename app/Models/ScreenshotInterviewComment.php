<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScreenshotInterviewComment extends Model
{
    protected $table = 'interview_comments';
    protected $primaryKey = 'interview_comments_id';
    public $timestamps = false;
}
