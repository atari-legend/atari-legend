<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PubDev extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['pub_dev_name', 'pub_dev_profile', 'pub_dev_imgext'];

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
        return Helper::filename($this->getKey(), $this->pub_dev_imgext);
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
