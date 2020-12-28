<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = ['number', 'version', 'issue', 'date', 'notes', 'menu_set_id'];

    protected $casts = [
        'date' => 'date',
    ];

    public function getLabelAttribute()
    {
        return ($this->number ?? '[no number]')
            .(($this->issue !== null) ? ' '.$this->issue : '')
            .(($this->version !== null) ? ' v' . $this->version : '');
    }

    public function menuSet()
    {
        return $this->belongsTo(MenuSet::class);
    }

    public function disks()
    {
        return $this->hasMany(MenuDisk::class);
    }
}
