<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ScreenshotArticle extends Pivot
{
    public $timestamps = false;

    public function comment()
    {
        return $this->hasOne(ScreenshotArticleComment::class, 'screenshot_article_id');
    }
}
