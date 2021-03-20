<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuDiskContent extends Model
{
    use HasFactory;

    protected $fillable = ['order', 'subtype', 'version', 'requirements', 'menu_disk_id'];

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

    public function getLabelAttribute()
    {
        if ($this->game) {
            return $this->game->game_name;
        } else if ($this->release) {
            return $this->release->game->game_name;
        } else if ($this->menuSoftware) {
            return $this->menuSoftware->name;
        } else {
            return "Unkown or empty content";
        }
    }
}
