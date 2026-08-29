<?php

namespace App\Models;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, CanResetPassword;

    const PERMISSION_ADMIN = 1;
    const PERMISSION_USER = 2;

    const ACTIVE = 0;
    const INACTIVE = 1;

    public $timestamps = false;

    /* The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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

    public function getAuthPasswordName()
    {
        return 'sha512_password';
    }

    public function getAvatarAttribute()
    {
        if ($this->avatar_ext !== null && $this->avatar_ext !== '') {
            return asset('storage/images/user_avatars/' . $this->getKey() . '.' . $this->avatar_ext);
        } else {
            return null;
        }
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function interviews()
    {
        return $this->hasMany(Interview::class);
    }

    public function news()
    {
        return $this->hasMany(News::class);
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function changelogs()
    {
        return $this->hasMany(Changelog::class);
    }

    public function newsSubmissions()
    {
        return $this->hasMany(NewsSubmission::class);
    }

    public function websiteSubmissions()
    {
        return $this->hasMany(WebsiteValidate::class);
    }

    public function gameSubmissions()
    {
        return $this->hasMany(GameSubmitInfo::class);
    }

    public function dumps()
    {
        return $this->hasMany(Dump::class);
    }

    /**
     * A user can only be deleted while nothing holds a RESTRICT on them.
     *
     * Exactly two relations block, and they are the two whose foreign key is
     * ON DELETE RESTRICT: game_submitinfo and dump. Without this guard,
     * deleting one of the 114 accounts holding such a row reaches the admin as
     * a raw 1451 error page, and takes an unattended user:delete-unverified
     * run down with it.
     *
     * Nothing else blocks, deliberately, and the distinction is what makes the
     * guard usable rather than an obstacle. Articles, interviews, news,
     * reviews, website and website_validate are SET NULL: the content survives
     * and the author blanks. game_votes is SET NULL too, so the vote survives
     * as an anonymous one. comments, change_log, menu_disk_dumps,
     * news_submission and bug_report either SET NULL or dangle harmlessly, and
     * the frontend renders a dangling user_id as a missing author. Making any
     * of those block would put 150 comment-holders permanently beyond
     * deletion, which is the opposite of the policy: a person who asks to be
     * removed can be.
     *
     * This lives on the model rather than in the controller because two
     * callers need it -- the admin screen and DeleteUnverifiedUsers, which has
     * to skip a blocked account rather than throw out of its loop.
     *
     * users.inactive is not consulted. It is a login gate that User::isActive()
     * combines with email_verified_at, and it says nothing about a person's
     * contributions, which stay attributed while an account is merely
     * inactive.
     */
    public function getIsDeletableAttribute(): bool
    {
        if ($this->gameSubmissions()->exists()) {
            return false;
        }

        return ! $this->dumps()->exists();
    }
}
