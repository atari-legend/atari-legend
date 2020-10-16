<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Spotlight extends Model
{
    protected $table = 'spotlight';
    public $timestamps = false;

    public function screenshot()
    {
        return $this->belongsTo(Screenshot::class, 'screenshot_id');
    }
}
