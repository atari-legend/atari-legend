<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuDiskDump extends Model
{
    use HasFactory;

    public function menuDisk()
    {
        return $this->hasOne(MenuDisk::class);
    }
}
