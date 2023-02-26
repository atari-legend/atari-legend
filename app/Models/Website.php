<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;

class Website extends Model
{
    protected $table = 'website';
    protected $primaryKey = 'website_id';
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getFileAttribute()
    {
        return Helper::filename($this->website_id, $this->website_imgext);
    }

    public function getPathAttribute()
    {
        return 'images/website_images/' . $this->file;
    }
}
