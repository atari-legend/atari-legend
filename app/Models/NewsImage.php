<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;

class NewsImage extends Model
{
    public $timestamps = false;

    const EXTENSIONS = ['png', 'jpg', 'jpeg', 'gif', 'webp'];

    protected $fillable = ['imgext'];

    public function getFileAttribute()
    {
        return Helper::filename($this->getKey(), $this->imgext);
    }
}
