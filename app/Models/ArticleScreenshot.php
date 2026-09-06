<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ArticleScreenshot extends Pivot
{
    public $timestamps = false;

    public function comment()
    {
        return $this->hasOne(ArticleScreenshotComment::class, 'article_screenshot_id');
    }
}
