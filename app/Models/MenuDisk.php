<?php

namespace App\Models;

use App\Http\Controllers\MenuSetController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
            $this->menu->label.$this->label,
        ])->join(' ');
    }

    /**
     * @return string Filename to use when downloading this disk, based on the
     *                menu set name and menu label
     */
    public function getDownloadFilenameAttribute()
    {
        return $this->download_basename.'.zip';
    }

    /**
     * @return number The page number, within the menu set, that contains this
     *                menu
     */
    public function getMenusetPageNumberAttribute()
    {
        $id = $this->id;

        $index = $this->menu
            ->menuSet
            ->menus
            ->flatMap(function ($menu) {
                return $menu->disks->map(function ($disk) use ($menu) {
                    return [
                        'id'      => $disk->id,
                        'number'  => $menu->number,
                        'issue'   => $menu->issue,
                        'version' => $menu->version,
                        'part'    => $disk->part,
                    ];
                })
                    ->all();
            })
            ->sortBy([
                ['number', $this->menu->menuSet->menus_sort],
                ['issue', $this->menu->menuSet->menus_sort],
                ['version', 'asc'],
                ['part', 'asc'],
            ])
            ->pluck('id')
            ->values()
            ->search($this->id);

        return ceil(($index + 1) / MenuSetController::PAGE_SIZE);
    }
}
