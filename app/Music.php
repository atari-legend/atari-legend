<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Music extends Model
{
    protected $table = "music";
    protected $primaryKey = "music_id";
    public $timestamps = false;
}
