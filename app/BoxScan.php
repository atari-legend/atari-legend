<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BoxScan extends Model
{
    protected $table = 'game_boxscan';
    protected $primaryKey = 'game_boxscan_id';
    public $timestamps = false;
}
