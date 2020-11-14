<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;

class PublisherDeveloperText extends Model
{
    protected $table = 'pub_dev_text';
    protected $primaryKey = 'pub_dev_text_id';
    public $timestamps = false;

    public function getFileAttribute()
    {
        return Helper::filename($this->pub_dev_id, $this->pub_dev_imgext);
    }
}
