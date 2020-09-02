<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class User extends Model implements \Illuminate\Contracts\Auth\Authenticatable
{
    use Authenticatable;

    const PERMISSION_ADMIN = 1;
    const PERMISSION_USER = 2;

    protected $primaryKey = "user_id";
    public $timestamps = false;

    protected $fillable = [
        'userid', 'email', 'password', 'user_website',
        'user_fb', 'user_twitter', 'user_af',
        'permission', 'join_date', 'inactive',
        'sha512_password', 'salt'
    ];

    public function reviews()
    {
        return $this->hasMany(Review::class, "user_id");
    }

    public function comments()
    {
        return $this->hasMany(GameComment::class, "user_id");
    }

}
