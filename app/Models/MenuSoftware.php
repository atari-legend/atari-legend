<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuSoftware extends Model
{
    use HasFactory;

    public function menuSoftwareContentType()
    {
        return $this->belongsTo(MenuSoftwareContentType::class);
    }

    public function menuDiskContents()
    {
        return $this->hasMany(MenuDiskContent::class);
    }

}
