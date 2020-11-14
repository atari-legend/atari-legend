<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndividualNick extends Model
{
    protected $table = 'individual_nicks';
    public $timestamps = false;

    public function nick()
    {
        return $this->belongsTo(Individual::class, 'nick_id');
    }
}
