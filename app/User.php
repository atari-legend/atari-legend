<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class User extends Model implements \Illuminate\Contracts\Auth\Authenticatable
{
    use Authenticatable;

    protected $primaryKey = "user_id";
    public $timestamps = false;

    public function reviews()
    {
        return $this->hasMany(Review::class, "user_id");
    }

    public function comments()
    {
        return $this->hasMany(GameComment::class, "user_id");
    }

}
