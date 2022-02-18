<?php

namespace App\Models;

use App\Http\Controllers\MenuSetController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MenuDisk extends Model
{
    use HasFactory;

    protected $fillable = ['part', 'notes', 'scrolltext', 'menu_disk_condition_id', 'donated_by_individual_id', 'menu_id'];

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

    /**
     * @return string Basename to use when downloading this disk, based on the
     *                menu set name and menu label
     */
    public function getDownloadBasenameAttribute()
    {
        return collect([
            $this->menu->menuSet->name,
            $this->menu->label . $this->label,
        ])->join(' ');
    }

    /**
     * @return string Filename to use when downloading this disk, based on the
     *                menu set name and menu label
     */
    public function getDownloadFilenameAttribute()
    {
        return $this->download_basename . '.zip';
    }

    /**
     * @return number The page number, within the menu set, that contains this
     *                menu
     */
    public function getMenusetPageNumberAttribute()
    {
        // Select all disks from the menu set, order them properly
        // and find the index of the current menu

        // Unfortunately MySQL does not support a way to compute row
        // numbers automatically, otherwise we could also ask it to
        // return the index. So we have to get the whole list, and
        // search the index in PHP

        $index = DB::table('menu_disks')
            ->select('menu_disks.id')
            ->join('menus', 'menus.id', '=', 'menu_disks.menu_id')
            ->join('menu_sets', 'menu_sets.id', '=', 'menus.menu_set_id')
            ->where('menu_set_id', '=', $this->menu->menuSet->id)
            ->orderBy('number', $this->menu->menuSet->menus_sort)
            ->orderBy('issue', $this->menu->menuSet->menus_sort)
            ->orderBy('version', 'asc')
            ->orderBy('part', 'asc')
            ->get()
            ->pluck('id')
            ->search($this->id);

        return ceil(($index + 1) / MenuSetController::PAGE_SIZE);
    }
}
