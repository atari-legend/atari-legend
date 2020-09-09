<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ScreenshotArticle extends Model
{
    protected $table = 'screenshot_article';
    protected $primaryKey = 'screenshot_article_id';
    public $timestamps = false;

    public function screenshot()
    {
        return $this->belongsTo(Screenshot::class, 'screenshot_id');
    }
}
