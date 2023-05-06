<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;

class ReleaseScan extends Model
{
    const TYPES = ['Box front', 'Box back', 'Goodie', 'Other'];

    protected $table = 'game_release_scan';
    public $timestamps = false;

    public function getFileAttribute()
    {
        return Helper::filename($this->id, $this->imgext);
    }

    public function getPathAttribute()
    {
        return 'images/game_release_scans/' . $this->file;
    }

    public function getUrlAttribute()
    {
        return asset('storage/' . $this->path);
    }
}
