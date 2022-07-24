<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function type()
    {
        return $this->belongsTo(MagazineIndexType::class);
    }
}
