<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuSoftware extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'demozoo_id', 'menu_software_content_type_id'];

    public function menuSoftwareContentType()
    {
        return $this->belongsTo(MenuSoftwareContentType::class);
    }

    public function menuDiskContents()
    {
        return $this->hasMany(MenuDiskContent::class);
    }
}
