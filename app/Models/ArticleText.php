<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleText extends Model
{
    protected $table = 'article_text';
    protected $primaryKey = 'article_text_id';
    public $timestamps = false;

    protected $casts = [
        'article_date' => 'datetime:timestamp',
    ];
}
