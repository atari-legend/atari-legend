<?php

namespace App\Http\Controllers\Admin\Menus;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Game;
use App\Models\MenuDisk;
use App\Models\MenuDiskContent;
use App\Models\MenuSoftware;
use App\Models\Release;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;

class MenuDisksContentController extends Controller
{
    public function create(Request $request, MenuDisk $disk)
    {
        $diskReleases = $disk->menu
            ->disks
            ->flatMap(function ($disk) {
                return $disk->contents;
            })
            ->filter(function ($content) {
                return $content->release !== null;
            })
            ->map(function ($content) {
                return $content->release;
            })
            ->unique();

        return view('admin.menus.disks.content.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb('', 'Menus'),
                    new Crumb(route('admin.menus.sets.index'), 'Sets'),
                    new Crumb(route('admin.menus.sets.edit', $disk->menu->menuSet), $disk->menu->menuSet->name),
                    new Crumb(route('admin.menus.menus.edit', $disk->menu), $disk->menu->label),
                    new Crumb(route('admin.menus.disks.edit', $disk), $disk->label ?: 'Disk'),
                    new Crumb('', 'Create Content'),
                ],
                'disk'         => $disk,
                'type'         => $request->type,
                'softwares'    => MenuSoftware::orderBy('name')->get(),
                'games'        => Game::orderBy('game_name')->get(),
                'diskReleases' => $diskReleases,
            ]);
    }

    public function store(Request $request)
    {
        $rules = [
            'order' => 'required|numeric',
        ];

        switch ($request->type) {
            case 'software':
                $rules['software'] = 'required';
                break;
            case 'game':
                $rules['game'] = 'required';
                $rules['subtype'] = 'required';
                break;
            case 'release':
                if ($request->action === 'create-release') {
                    $rules['game'] = 'required';
                } elseif ($request->action === 'use-release') {
                    $rules['release'] = 'required';
                    $rules['subtype'] = 'required';
                } else {
                    throw new \ErrorException("Unsupported action: {$request->action}");
                }
                break;
            default:
                throw new \ErrorException("Unsupported type: {$request->type}");
        }

        $request->validate($rules);

        $disk = MenuDisk::find($request->disk);
        $content = MenuDiskContent::create([
            'order'        => $request->order,
            'subtype'      => $request->subtype,
            'version'      => $request->version,
            'requirements' => $request->requirements,
            'menu_disk_id' => $disk->id,
        ]);

        switch ($request->type) {
            case 'software':
                $software = MenuSoftware::find($request->software);
                $software->menuDiskContents()->save($content);
                break;
            case 'release':
                if ($request->action === 'create-release') {
                    $release = Release::create([
                        'type'    => Release::TYPE_UNOFFICIAL,
                        'game_id' => $request->game,
                    ]);
                    $release->menuDiskContents()->save($content);
                } elseif ($request->action === 'use-release') {
                    $release = Release::find($request->release);
                    $release->menuDiskContents()->save($content);
                }
                break;
            case 'game':
                $game = Game::find($request->game);
                $game->menuDiskContents()->save($content);
                break;
        }

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Menu Disks',
            'section_id'       => $disk->getKey(),
            'section_name'     => $disk->label,
            'sub_section'      => 'Content',
            'sub_section_id'   => $content->getKey(),
            'sub_section_name' => $content->label,
        ]);

        return redirect()->route('admin.menus.disks.edit', $disk);
    }

    public function edit(MenuDisk $disk, MenuDiskContent $content)
    {
        return view('admin.menus.disks.content.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb('', 'Menus'),
                    new Crumb(route('admin.menus.sets.index'), 'Sets'),
                    new Crumb(route('admin.menus.sets.edit', $content->menuDisk->menu->menuSet), $content->menuDisk->menu->menuSet->name),
                    new Crumb(route('admin.menus.menus.edit', $content->menuDisk->menu), $content->menuDisk->menu->label),
                    new Crumb(route('admin.menus.disks.edit', $content->menuDisk), $content->menuDisk->label ?: 'Disk'),
                    new Crumb(route('admin.menus.disks.content.edit', [
                        'disk'    => $content->menuDisk,
                        'content' => $content,
                    ]), $content->label),
                ],
                'disk'         => $content->menuDisk,
                'content'      => $content,
            ]);
    }

    public function update(Request $request, MenuDisk $disk, MenuDiskContent $content)
    {
        $request->validate([
            'order' => 'required|numeric',
        ]);

        if ($content->game !== null) {
            $request->validate([
                'subtype' => 'required',
            ]);
        }

        $content->update([
            'order'        => $request->order,
            'subtype'      => $request->subtype,
            'version'      => $request->version,
            'requirements' => $request->requirements,
        ]);

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Menu Disks',
            'section_id'       => $disk->getKey(),
            'section_name'     => $disk->label,
            'sub_section'      => 'Content',
            'sub_section_id'   => $content->getKey(),
            'sub_section_name' => $content->label,
        ]);

        $request->session()->flash('alert-success', 'Saved');

        return redirect()->route('admin.menus.disks.edit', $content->menuDisk);
    }

    public function destroy(MenuDisk $disk, MenuDiskContent $content)
    {
        $content->delete();

        // If it's a release we should also delete it, unless it's a doc / hint / etc.
        if ($content->release && $content->subtype === null) {
            // Delete all content associated with this release (docs, hints, ...)
            $content->release->menuDiskContents
                ->filter(function ($otherContent) use ($content) {
                    return $content->id !== $otherContent->id;
                })
                ->each(function ($otherContent) {
                    $otherContent->delete();
                });

            // Delete the release
            $content->release->delete();
        }

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Menu Disks',
            'section_id'       => $disk->getKey(),
            'section_name'     => $disk->label,
            'sub_section'      => 'Content',
            'sub_section_id'   => $content->getKey(),
            'sub_section_name' => $content->label,
        ]);

        return redirect()->route('admin.menus.disks.edit', $content->menuDisk);
    }
}
