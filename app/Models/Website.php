<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Website extends Model
{
    protected $table = 'website';
    protected $primaryKey = 'website_id';
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFileAttribute()
    {
        return $this->website_id
            .'.'
            .$this->website_imgext;
    }
}
