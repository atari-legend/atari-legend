<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuSet extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function crews()
    {
        return $this->belongsToMany(Crew::class, null, null, 'crew_id');
    }

    public function menus()
    {
        return $this->hasMany(Menu::class);
    }
}
