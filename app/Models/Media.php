<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $table = 'media';
    public $timestamps = false;

    public function type()
    {
        return $this->belongsTo(MediaType::class, 'media_type_id');
    }

    public function dumps()
    {
        return $this->hasMany(Dump::class);
    }

    public function scans()
    {
        return $this->hasMany(MediaScan::class);
    }

    public function release()
    {
        return $this->belongsTo(Release::class);
    }
}
