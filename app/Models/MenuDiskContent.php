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

    public function menuDiskContentType()
    {
        return $this->belongsTo(MenuDiskContentType::class);
    }

    public function release()
    {
        return $this->belongsTo(Release::class, 'game_release_id');
    }

    public function getContentNameAttribute()
    {
        if ($this->release) {
            return $this->release->name ?? $this->release->game->game_name;
        } else {
            return $this->name;
        }
    }
}
