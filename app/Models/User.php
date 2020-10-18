<?php

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory;
    use Notifiable;
    use CanResetPassword;

    const PERMISSION_ADMIN = 1;
    const PERMISSION_USER = 2;

    protected $primaryKey = 'user_id';
    public $timestamps = false;

    protected $fillable = [
        'userid', 'email', 'password', 'user_website',
        'user_fb', 'user_twitter', 'user_af',
        'permission', 'join_date', 'inactive',
        'sha512_password', 'salt',
    ];

    public function reviews()
    {
        return $this->hasMany(Review::class, 'user_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'user_id');
    }

    public function newsSubmissions()
    {
        return $this->hasMany(NewsSubmission::class, 'user_id');
    }

    public function websiteSubmissions()
    {
        return $this->hasMany(WebsiteValidate::class, 'user_id');
    }

    public function gameSubmissions()
    {
        return $this->hasMany(GameSubmitInfo::class, 'user_id');
    }
}
