<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MagazineIndex extends Model
{
    use HasFactory;

    protected $fillable = ['game_id'];

    public function magazineIssue()
    {
        return $this->belongsTo(MagazineIssue::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    public function menuSoftware()
    {
        return $this->belongsTo(MenuSoftware::class);
    }

    public function magazineIndexTtype()
    {
        return $this->belongsTo(MagazineIndexType::class);
    }

    public function getReadUrlAttribute()
    {
        if ($this->magazineIssue->archiveorg_url) {
            return Str::replace('/details/', '/stream/', $this->magazineIssue->archiveorg_url)
                . ($this->page ? '#page/' . $this->page : '');
        } else {
            return null;
        }
    }
}
