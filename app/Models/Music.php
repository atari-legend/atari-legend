<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Music extends Model
{
    protected $table = 'music';
    protected $primaryKey = 'music_id';
    public $timestamps = false;
}
