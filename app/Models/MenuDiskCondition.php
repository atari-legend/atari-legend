<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuDiskCondition extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function menuDisks()
    {
        return $this->hasMany(MenuDisk::class);
    }
}
