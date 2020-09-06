<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Screenshot extends Model
{
    protected $table = 'screenshot_main';
    protected $primaryKey = 'screenshot_id';
    public $timestamps = false;

    public function getFileAttribute()
    {
        return $this->screenshot_id
            .'.'
            .$this->imgext;
    }
}
