<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameVideo extends Model
{
    protected $fillable = ['youtube_id', 'game_id'];
}
