<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScreenshotArticleComment extends Model
{
    public $timestamps = false;
    protected $fillable = ['comment_text'];
}
