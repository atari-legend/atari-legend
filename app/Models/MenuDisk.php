<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuDisk extends Model
{
    use HasFactory;

    protected $fillable = ['part', 'notes', 'scrolltext', 'menu_id'];

    public function screenshots()
    {
        return $this->hasMany(MenuDiskScreenshot::class);
    }

    public function contents()
    {
        return $this->hasMany(MenuDiskContent::class);
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function menuDiskDump()
    {
        return $this->belongsTo(MenuDiskDump::class);
    }

    public function menuDiskCondition()
    {
        return $this->belongsTo(MenuDiskCondition::class);
    }

    public function donatedBy()
    {
        return $this->belongsTo(Individual::class, 'donated_by_individual_id', 'ind_id');
    }

    public function getLabelAttribute()
    {
        return $this->part ?? '';
    }
}
