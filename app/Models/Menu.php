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
        $parts = [];
        if ($this->number !== null) {
            $parts[] = '#'.$this->number;
        } elseif ($this->issue !== null) {
            $parts[] = $this->issue;
        } else {
            $parts[] = '#[no number]';
        }

        if ($this->version) {
            $parts[] = ' v'.$this->version;
        }

        return collect($parts)
            ->join(' ');
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
