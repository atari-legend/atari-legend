<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NewsImage extends Model
{
    protected $table = 'news_image';
    protected $primaryKey = 'news_image_id';
    public $timestamps = false;
}
