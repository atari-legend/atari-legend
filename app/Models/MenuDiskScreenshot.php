<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuDiskScreenshot extends Model
{
    use HasFactory;

    public function getFileAttribute()
    {
        return Helper::filename($this->id, $this->imgext);
    }
}
