<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;

class NewsImage extends Model
{
    public $timestamps = false;

    protected $fillable = ['news_image_ext'];

    public function getFileAttribute()
    {
        return Helper::filename($this->getKey(), $this->news_image_ext);
    }
}
