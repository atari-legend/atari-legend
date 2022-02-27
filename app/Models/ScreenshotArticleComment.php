<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScreenshotArticleComment extends Model
{
    protected $table = 'article_comments';
    protected $primaryKey = 'article_comment_id';
    public $timestamps = false;
    protected $fillable = ['comment_text'];
}
