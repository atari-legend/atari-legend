<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    public $timestamps = false;

    const EXTENSIONS = ['png', 'jpg', 'jpeg', 'gif', 'webp'];

    protected $fillable = ['name', 'profile', 'imgext'];

    public function games()
    {
        return $this->belongsToMany(Game::class, 'game_developer');
    }

    public function releases()
    {
        return $this->hasMany(GameRelease::class);
    }

    public function getFileAttribute()
    {
        return Helper::filename($this->getKey(), $this->imgext);
    }

    public function getPathAttribute()
    {
        return 'images/company_logos/' . $this->file;
    }

    public function getLogoAttribute()
    {
        if ($this->file) {
            return asset('storage/' . $this->path);
        } else {
            return null;
        }
    }
}
