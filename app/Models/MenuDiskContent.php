<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuDiskContent extends Model
{
    use HasFactory;

    public function menuDisk()
    {
        return $this->belongsTo(MenuDisk::class);
    }

    public function release()
    {
        return $this->belongsTo(Release::class, 'game_release_id');
    }

    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    public function menuSoftware()
    {
        return $this->belongsTo(MenuSoftware::class);
    }
}
