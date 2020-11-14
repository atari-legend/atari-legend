<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaScan extends Model
{
    protected $table = 'media_scan';
    public $timestamps = false;

    public function getFileAttribute()
    {
        if ($this->imgext !== null) {
            return $this->id
                .'.'
                .$this->imgext;
        }
    }

    public function type()
    {
        return $this->belongsTo(MediaScanType::class, 'media_scan_type_id');
    }
}
