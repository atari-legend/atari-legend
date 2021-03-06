<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuDiskScreenshot extends Model
{
    use HasFactory;

    protected $fillable = ['menu_disk_id', 'imgext'];

    public function menuDisk()
    {
        return $this->belongsTo(MenuDisk::class);
    }

    public function getFileAttribute()
    {
        return Helper::filename($this->id, $this->imgext);
    }
}
