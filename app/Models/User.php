<?php

namespace App\Models;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory;
    use Notifiable;
    use CanResetPassword;

    const PERMISSION_ADMIN = 1;
    const PERMISSION_USER = 2;

    const ACTIVE = 0;
    const INACTIVE = 1;

    protected $primaryKey = 'user_id';
    public $timestamps = false;

    protected $fillable = [
        'userid', 'email', 'password', 'avatar_ext',
        'user_website', 'user_fb', 'user_twitter', 'user_af',
        'permission', 'join_date', 'inactive',
        'sha512_password', 'salt',
    ];

    /**
     * Determine if the user has verified their email address.
     *
     * We must consider both the Laravel 'email_verified_at' column and the
     * legacy Atari Legend 'inactive' one.
     *
     * @return bool
     */
    public function hasVerifiedEmail()
    {
        return ! is_null($this->email_verified_at) && $this->inactive === User::ACTIVE;
    }

    /**
     * Mark the given user's email as verified.
     *
     * We must update the Laravel 'email_verified_at'  column, and the legacy
     * Atari Legend 'inactive' one
     *
     * @return bool
     */
    public function markEmailAsVerified()
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    public function getAvatarAttribute()
    {
        if ($this->avatar_ext !== null && $this->avatar_ext !== '') {
            return asset('storage/images/user_avatars/' . $this->user_id . '.' . $this->avatar_ext);
        } else {
            return null;
        }
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'user_id');
    }

    public function news()
    {
        return $this->hasMany(News::class, 'user_id');
    }

    public function articles()
    {
        return $this->hasMany(Article::class, 'user_id');
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
