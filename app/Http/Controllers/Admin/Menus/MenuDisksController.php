<?php

namespace App\Http\Controllers\Admin\Menus;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Individual;
use App\Models\Menu;
use App\Models\MenuDisk;
use App\Models\MenuDiskCondition;
use App\Models\MenuDiskDump;
use App\Models\MenuDiskScreenshot;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

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
                'conditions'  => MenuDiskCondition::orderBy('name')->get(),
                'individuals' => Individual::orderBy('ind_name')->get(),
                'menu'        => $menu,
            ]);
    }

    public function store(Request $request)
    {
        $menu = Menu::find($request->menu);
        $disk = MenuDisk::create([
            'part'                     => $request->part,
            'notes'                    => $request->notes,
            'scrolltext'               => $request->scrolltext,
            'menu_disk_condition_id'   => $request->condition,
            'donated_by_individual_id' => $request->donated,
            'menu_id'                  => $menu->id,
        ]);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Menu Disks',
            'section_id'       => $disk->getKey(),
            'section_name'     => $disk->label,
            'sub_section'      => 'Disk',
            'sub_section_id'   => $disk->getKey(),
            'sub_section_name' => $disk->label,
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
                    new Crumb(route('admin.menus.disks.edit', $disk), $disk->label),
                ],
                'conditions'  => MenuDiskCondition::orderBy('name')->get(),
                'individuals' => Individual::orderBy('ind_name')->get(),
                'menu'        => $disk->menu,
                'disk'        => $disk,
            ]);
    }

    public function update(Request $request, MenuDisk $disk)
    {
        $disk->update([
            'part'                     => $request->part,
            'notes'                    => $request->notes,
            'scrolltext'               => $request->scrolltext,
            'menu_disk_condition_id'   => $request->condition,
            'donated_by_individual_id' => $request->donated,
        ]);

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Menu Disks',
            'section_id'       => $disk->getKey(),
            'section_name'     => $disk->getOriginal('label'),
            'sub_section'      => 'Disk',
            'sub_section_id'   => $disk->getKey(),
            'sub_section_name' => $disk->label,
        ]);

        $request->session()->flash('alert-success', 'Saved');

        return redirect()->route('admin.menus.disks.edit', $disk);
    }

    public function storeScreenshot(Request $request, MenuDisk $disk)
    {
        if ($request->hasFile('screenshot')) {
            $screenshotFile = $request->file('screenshot');
            $screenshot = MenuDiskScreenshot::create([
                'menu_disk_id' => $disk->id,
                'imgext'       => strtolower($screenshotFile->extension()),
            ]);

            $screenshotFile->storeAs('images/menu_screenshots/', $screenshot->id.'.'.$screenshotFile->extension(), 'public');

            ChangelogHelper::insert([
                'action'           => Changelog::INSERT,
                'section'          => 'Menu Disks',
                'section_id'       => $disk->getKey(),
                'section_name'     => $disk->label,
                'sub_section'      => 'Screenshot',
                'sub_section_id'   => $screenshot->getKey(),
                'sub_section_name' => $screenshot->imgext,
            ]);
        }

        return redirect()->route('admin.menus.disks.edit', $disk);
    }

    public function destroyScreenshot(MenuDisk $disk, MenuDiskScreenshot $screenshot)
    {
        if ($screenshot->menuDisk->id === $disk->id) {
            Storage::disk('public')->delete('images/menu_screenshots/'.$screenshot->id.'.'.$screenshot->imgext);
            $screenshot->delete();

            ChangelogHelper::insert([
                'action'           => Changelog::DELETE,
                'section'          => 'Menu Disks',
                'section_id'       => $disk->getKey(),
                'section_name'     => $disk->label,
                'sub_section'      => 'Screenshot',
                'sub_section_id'   => $screenshot->getKey(),
                'sub_section_name' => $screenshot->imgext,
            ]);
        }

        return redirect()->route('admin.menus.disks.edit', $disk);
    }

    public function storeDump(Request $request, MenuDisk $disk)
    {
        if ($request->hasFile('dump')) {
            $dumpFile = $request->file('dump');
            $clientExt = strtoupper($dumpFile->getClientOriginalExtension());

            $dumpFormat = null;
            $dumpSize = null;
            $dumpChecksum = null;

            $tmpFilePath = tempnam(sys_get_temp_dir(), 'dump');
            $dumpZip = new ZipArchive();
            $dumpZip->open($tmpFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            if ($clientExt !== 'ZIP' && !collect(MenuDiskDump::EXTENSIONS)->contains($clientExt)) {
                $request->session()->flash('alert-danger', 'Unsupported file extension: '.$clientExt);

                return redirect()->route('admin.menus.disks.edit', $disk);
            }

            if ($clientExt === 'ZIP') {
                $zip = new ZipArchive();
                if ($zip->open($dumpFile->path()) !== true) {
                    $request->session()->flash('alert-danger', 'Error opening ZIP file: '.$zip->getStatusString());

                    return redirect()->route('admin.menus.disks.edit', $disk);
                }
                if ($zip->count() !== 1) {
                    $request->session()->flash('alert-danger', 'More than one file in the ZIP archive. Please only include a single disk image.');
                    $zip->close();

                    return redirect()->route('admin.menus.disks.edit', $disk);
                }

                $zipEntryName = $zip->getNameIndex(0);
                $zipEntryExt = strtoupper(pathinfo($zipEntryName, PATHINFO_EXTENSION));

                if (!collect(MenuDiskDump::EXTENSIONS)->contains($zipEntryExt)) {
                    $request->session()->flash('alert-danger', 'File insize ZIP as an unsupported file extension: '.$zipEntryExt);
                    $zip->close();

                    return redirect()->route('admin.menus.disks.edit', $disk);
                }

                $content = $zip->getFromIndex(0);
                $dumpFormat = strtoupper($zipEntryExt);
                $dumpSize = strlen($content);
                $dumpChecksum = hash('sha512', $content);

                $dumpZip->addFromString($disk->download_basename.'.'.strtolower($zipEntryExt), $content);
            } else {
                $dumpFormat = $clientExt;
                $dumpSize = strlen($dumpFile->get());
                $dumpChecksum = hash('sha512', $dumpFile->get());

                $dumpZip->addFile($dumpFile->path(), $disk->download_basename.'.'.strtolower($clientExt));
            }
            $dumpZip->close();

            $dump = null;
            if ($disk->menuDiskDump !== null) {
                $disk->menuDiskDump->update([
                    'user_id' => Auth::user()->user_id,
                    'format'  => $dumpFormat,
                    'sha512'  => $dumpChecksum,
                    'size'    => $dumpSize,
                ]);
                $dump = $disk->menuDiskDump;

                ChangelogHelper::insert([
                    'action'           => Changelog::UPDATE,
                    'section'          => 'Menu Disks',
                    'section_id'       => $disk->getKey(),
                    'section_name'     => $disk->label,
                    'sub_section'      => 'Dump',
                    'sub_section_id'   => $dump->getKey(),
                    'sub_section_name' => $dump->format,
                ]);
            } else {
                $dump = MenuDiskDump::create([
                    'user_id' => Auth::user()->user_id,
                    'format'  => $dumpFormat,
                    'sha512'  => $dumpChecksum,
                    'size'    => $dumpSize,
                ]);
                $disk->menuDiskDump()->associate($dump);
                $disk->save();

                ChangelogHelper::insert([
                    'action'           => Changelog::INSERT,
                    'section'          => 'Menu Disks',
                    'section_id'       => $disk->getKey(),
                    'section_name'     => $disk->label,
                    'sub_section'      => 'Dump',
                    'sub_section_id'   => $dump->getKey(),
                    'sub_section_name' => $dump->format,
                ]);
            }

            $handle = fopen($tmpFilePath, 'r');
            Storage::disk('public')->put('zips/menus/'.$dump->id.'.zip', $handle);
            fclose($handle);
            unlink($tmpFilePath);
        }

        return redirect()->route('admin.menus.disks.edit', $disk);
    }

    public function destroyDump(MenuDisk $disk, MenuDiskDump $dump)
    {
        if ($dump->menuDisk->id === $disk->id) {
            Storage::disk('public')->delete('zips/menus/'.$dump->id.'.zip');
            $dump->menuDisk->menuDiskDump()->dissociate();
            $dump->menuDisk->save();
            $dump->delete();

            ChangelogHelper::insert([
                'action'           => Changelog::DELETE,
                'section'          => 'Menu Disks',
                'section_id'       => $disk->getKey(),
                'section_name'     => $disk->label,
                'sub_section'      => 'Dump',
                'sub_section_id'   => $dump->getKey(),
                'sub_section_name' => $dump->format,
            ]);
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

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Menu Disks',
            'section_id'       => $disk->getKey(),
            'section_name'     => $disk->label,
            'sub_section'      => 'Disk',
            'sub_section_id'   => $disk->getKey(),
            'sub_section_name' => $disk->label,
        ]);

        return redirect()->route('admin.menus.menus.edit', $disk->menu);
    }
}
