<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;

class ReleaseScan extends Model
{
    const TYPE_OTHER = 'Other';
    const TYPE_BOX_FRONT = 'Box front';
    const TYPE_BOX_BACK = 'Box back';
    const TYPES = [ReleaseScan::TYPE_BOX_FRONT, ReleaseScan::TYPE_BOX_BACK, 'Goodie', ReleaseScan::TYPE_OTHER];

    protected $table = 'game_release_scan';
    public $timestamps = false;
    protected $fillable = ['game_release_id', 'imgext', 'type'];

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
