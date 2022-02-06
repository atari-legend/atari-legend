<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Spotlight extends Model
{
    protected $primaryKey = 'spotlight_id';
    protected $table = 'spotlight';
    public $timestamps = false;

    protected $fillable = ['spotlight', 'link'];

    public function screenshot()
    {
        return $this->belongsTo(Screenshot::class, 'screenshot_id');
    }
}
