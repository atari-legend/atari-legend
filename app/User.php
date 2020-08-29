<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $primaryKey = "user_id";
    public $timestamps = false;

    public function reviews()
    {
        return $this->hasMany(Review::class, "user_id");
    }

}
