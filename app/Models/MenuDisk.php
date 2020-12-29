<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuDisk extends Model
{
    use HasFactory;

    public function screenshots()
    {
        return $this->hasMany(MenuDiskScreenshot::class);
    }

    public function dumps()
    {
        return $this->hasMany(MenuDiskDump::class);
    }

    public function contents()
    {
        return $this->hasMany(MenuDiskContent::class);
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}
