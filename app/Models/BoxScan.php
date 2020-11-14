<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;

class BoxScan extends Model
{
    protected $table = 'game_boxscan';
    protected $primaryKey = 'game_boxscan_id';
    public $timestamps = false;

    public function getFileAttribute()
    {
        return Helper::filename($this->game_boxscan_id, $this->imgext);
    }
}
