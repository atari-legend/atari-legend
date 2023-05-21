<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;

class MediaScan extends Model
{
    protected $table = 'media_scan';
    public $timestamps = false;

    protected $fillable = ['imgext'];

    public function getFileAttribute()
    {
        return Helper::filename($this->id, $this->imgext);
    }

    public function type()
    {
        return $this->belongsTo(MediaScanType::class, 'media_scan_type_id');
    }

    public function media()
    {
        return $this->belongsTo(Media::class);
    }

    public function getUrlAttribute()
    {
        return asset('storage/' . $this->path);
    }

    public function getPathAttribute()
    {
        return 'images/media_scans/' . $this->file;
    }
}
