<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuSoftwareContentType extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function contents()
    {
        return $this->hasMany(MenuSoftware::class);
    }
}
