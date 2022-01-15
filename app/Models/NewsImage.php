<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;

class NewsImage extends Model
{
    protected $table = 'news_image';
    protected $primaryKey = 'news_image_id';
    public $timestamps = false;

    protected $fillable = ['news_image_ext'];

    public function getFileAttribute()
    {
        return Helper::filename($this->news_image_id, $this->news_image_ext);
    }
}
