<?php

namespace App\Http\Controllers\Admin\Menus;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuDisk;
use App\Models\MenuDiskContent;
use App\Models\MenuDiskScreenshot;
use App\Models\MenuSet;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MenuDisksController extends Controller
{

    public function create(Request $request)
    {
        $menu = Menu::find($request->menu);

        return view('admin.menus.disks.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb('', 'Menus'),
                    new Crumb(route('admin.menus.sets.index'), 'Sets'),
                    new Crumb(route('admin.menus.sets.edit', $menu->menuSet), $menu->menuSet->name),
                    new Crumb(route('admin.menus.menus.edit', $menu), $menu->label),
                    new Crumb('', 'Create disk'),
                ],
                'menu'         => $menu,
            ]);
    }

    public function store(Request $request)
    {
        $menu = Menu::find($request->menu);
        $disk = MenuDisk::create([
            'part'       => $request->part,
            'notes'      => $request->notes,
            'scrolltext' => $request->scrolltext,
            'menu_id'    => $menu->id,
        ]);

        return redirect()->route('admin.menus.disks.edit', $disk);
    }

    public function edit(MenuDisk $disk)
    {
        return view('admin.menus.disks.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb('', 'Menus'),
                    new Crumb(route('admin.menus.sets.index'), 'Sets'),
                    new Crumb(route('admin.menus.sets.edit', $disk->menu->menuSet), $disk->menu->menuSet->name),
                    new Crumb(route('admin.menus.menus.edit', $disk->menu), $disk->menu->label),
                    new Crumb(route('admin.menus.disks.edit', $disk), $disk->label)
                ],
                'menu'        => $disk->menu,
                'disk'        => $disk,
            ]);
    }

    public function update(Request $request, MenuDisk $disk)
    {
        $disk->update([
            'part'       => $request->part,
            'notes'      => $request->notes,
            'scrolltext' => $request->scrolltext,
        ]);

        $request->session()->flash('alert-success', 'Saved');
        return redirect()->route('admin.menus.disks.edit', $disk);
    }

    public function addScreenshot(Request $request, MenuDisk $disk)
    {
        if ($request->hasFile('screenshot')) {
            $screenshotFile = $request->file('screenshot');
            $screenshot = MenuDiskScreenshot::create([
                'menu_disk_id' => $disk->id,
                'imgext'       => strtolower($screenshotFile->extension()),
            ]);

            $screenshotFile->storeAs('images/menu_screenshots/', $screenshot->id . '.' . $screenshotFile->extension(), 'public');
        }

        return redirect()->route('admin.menus.disks.edit', $disk);
    }

    public function destroyScreenshot(Request $request, MenuDisk $disk, MenuDiskScreenshot $screenshot)
    {
        if ($screenshot->menuDisk->id === $disk->id) {
            Storage::disk('public')->delete('images/menu_screenshots/' . $screenshot->id . '.' . $screenshot->imgext);
            $screenshot->delete();
        }
        return redirect()->route('admin.menus.disks.edit', $disk);
    }

    public function destroy(MenuDisk $disk)
    {
        // Delete all content, but collect releases to delete them afterwards
        $disk->contents
            ->map(function ($content) {
                $content->delete();
                return $content->release;
            })
            ->filter(function ($release) {
                return $release !== null;
            })
            ->each(function ($release) {
                $release->delete();
            });

        $disk->delete();

        return redirect()->route('admin.menus.menus.edit', $disk->menu);
    }
}
