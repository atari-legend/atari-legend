<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameVs extends Model
{
    protected $table = 'game_vs';
    protected $primaryKey = 'atari_id';
    public $timestamps = false;
}
