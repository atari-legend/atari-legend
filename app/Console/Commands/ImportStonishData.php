<?php

namespace App\Console\Commands;

use App\Helpers\ChangelogHelper;
use App\Models\Changelog;
use App\Models\Crew;
use App\Models\Game;
use App\Models\Individual;
use App\Models\Menu;
use App\Models\MenuDisk;
use App\Models\MenuDiskCondition;
use App\Models\MenuDiskContent;
use App\Models\MenuDiskContentType;
use App\Models\MenuDiskDump;
use App\Models\MenuDiskScreenshot;
use App\Models\MenuSet;
use App\Models\Release;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ImportStonishData extends Command
{

    private $stop = false;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stonish:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Stonish menus data';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        pcntl_signal(SIGINT, [$this, 'interrupt']);

        Release::has('menuDiskContents')->each(function ($release) {
            $release->menuDiskContents->each(function ($content) {
                $content->release()->dissociate();
                $content->save();
            });
            $release->delete();
        });

        DB::table('menu_disk_contents')->delete();
        DB::table('menu_disk_screenshots')->delete();
        DB::table('menu_disks')->delete();
        DB::table('crew_menu_set')->delete();
        DB::table('menus')->delete();
        DB::table('menu_sets')->delete();

        foreach (Storage::disk('public')->files('images/menu_screenshots/') as $file) {
            Storage::disk('public')->delete($file);
        }
        foreach (Storage::disk('public')->files('zips/menus/') as $file) {
            Storage::disk('public')->delete($file);
        }

        $db = DB::connection('stonish');

        $menus = $db->table('allmenus')
            ->join('namemenus', 'name', '=', 'namemenus.id_namemenus')
            ->select('allmenus.*', 'namemenus.name_menus')
            ->orderBy('name_menus')
            ->orderBy('issue')
            ->orderBy('letter')
            ->orderBy('version')
            ->get();

        foreach ($menus as $stonishMenu) {
            if ($this->stop) {
                break;
            }

            $this->info("{$stonishMenu->name_menus} #{$stonishMenu->issue} {$stonishMenu->letter} {$stonishMenu->version}");

            $crew = $this->getCrew($stonishMenu);
            $menuset = $this->getMenuSet($crew, $stonishMenu);
            $menu = $this->getMenu($menuset, $stonishMenu);
            $disk = $this->getMenuDisk($menu, $stonishMenu);
            $dump = $this->getDump($disk, $stonishMenu);

            $contents = $db->table('allcontent')
                ->join('software', 'content', '=', 'software.id_software')
                ->select('allcontent.*', 'software.*')
                ->where('id_menus', '=', $stonishMenu->id_allmenus)
                ->get();

            foreach ($contents as $content) {
                $this->info("\t\t\t{$content->titlesoft} Type:{$content->typeofsoftware} SubType:{$content->type} AL:{$content->idlegend} DZ:{$content->iddemozoo}");
                $menuDiskContent = new MenuDiskContent();
                $menuDiskContent->name = $content->titlesoft;   // Remove if AL link?

                $notes = [];
                if ($content->version_software !== null && trim($content->version_software) !== '') {
                    $notes[] = trim($content->version_software);
                }
                if ($content->requirement !== null && trim($content->requirement) !== '') {
                    $notes[] = trim($content->requirement);
                }

                $menuDiskContent->notes = (count($notes) > 0) ? join("'\n", $notes) : null;

                if ($content->iddemozoo) {
                    $menuDiskContent->demozoo_id = $content->iddemozoo;
                }
                $menuDiskContent->menu_disk_content_type_id = $content->typeofsoftware;
                if ($content->type !== null && trim($content->type) !== '') {
                    $menuDiskContent->subtype = trim($content->type);
                }

                if ($content->idlegend) {
                    $game = Game::find($content->idlegend);
                    if ($game !== null) {
                        // Remove the software name, since it should be read
                        // from the release / game
                        $menuDiskContent->name = null;

                        // Find if there is already a release of this game on the same
                        // disk
                        $release = Release::whereHas('menuDiskContents', function (Builder $query) use ($disk) {
                            $query->where('menu_disk_id', '=', $disk->id);
                        })
                            ->where('game_id', '=', $game->game_id)
                            ->first();
                        if (!$release) {
                            // Create a new release
                            $release = new Release();
                            $release->type = 'Unofficial';
                            $release->game_id = $game->game_id;
                            if ($menu->date) {
                                $release->date = $menu->date;
                            }
                            $release->save();
                        }

                        // Associate release with content
                        $menuDiskContent->game_release_id = $release->id;
                    } else {
                        $this->warn("Could not find AL game with ID {$content->idlegend} for title {$content->titlesoft}");
                    }
                }

                $disk->contents()->save($menuDiskContent);
            }


            $this->info("\t\tImported menu!");
        }
        return 0;
    }

    private function getCrew(object $menu): Crew
    {
        $crews = Crew::whereRaw('LOWER(TRIM(crew_name)) = ?', strtolower(trim($menu->name_menus)));
        $crew = null;
        if ($crews->count() === 1) {
            $crew = $crews->first();
            $this->info("\tFound crew {$crew->crew_id} {$crew->crew_name}");
        } else if ($crews->count() < 1) {
            $this->warn("\tNo crew found with name {$menu->name_menus}. Creating a new one");
            $crew = Crew::create([
                'crew_name' => $menu->name_menus
            ]);
            ChangelogHelper::insert([
                'action'           => Changelog::INSERT,
                'section'          => 'Crew',
                'section_id'       => $crew->getKey(),
                'section_name'     => $crew->crew_name,
                'sub_section'      => 'Crew',
                'sub_section_id'   => $crew->getKey(),
                'sub_section_name' => $crew->crew_name,
            ]);
        } else if ($crews->count() > 1) {
            $this->error("\tMore than 1 crew found for {$menu->name_menus}");
            $this->error("\t" . $crews->pluck('crew_id')->join(', '));
            exit(1);
        }

        return $crew;
    }

    private function getMenuSet(Crew $crew, object $menu): MenuSet
    {
        $menusets = MenuSet::whereRaw('LOWER(TRIM(name)) = ?', strtolower(trim($menu->name_menus)));
        $menuset = null;
        if ($menusets->count() === 1) {
            $menuset = $menusets->first();
            $this->info("\tFound menu set {$menuset->id} {$menuset->name}");
        } else if ($menusets->count() < 1) {
            $this->warn("\tNo menu set found with name {$menu->name_menus}. Creating a new one");
            $menuset = new MenuSet();
            $menuset->name = $menu->name_menus;
            if ($crew->crew_name === 'Euroswap') {
                // Special case: Euroswap menus are sorted from highest to lowest
                $menuset->menus_sort = 'descending';
            }
            $crew->menuSets()->save($menuset);
        } else if ($menusets->count() > 2) {
            $this->error("\tMore than 1 menu set found for {$menu->name_menus}");
            $this->error("\t" . $menusets->pluck('id')->join(', '));
            exit(1);
        }

        return $menuset;
    }

    private function getMenu(MenuSet $menuset, object $stonishMenu): Menu
    {
        $menus = Menu::where('menu_set_id', $menuset->id);

        if ($stonishMenu->issue !== null) {
            $menus = $menus->where('number', $stonishMenu->issue);
        } else if ($stonishMenu->letter !== null && trim($stonishMenu->letter) !== '') {
            $menus = $menus->where('issue', trim($stonishMenu->letter));
        } else {
            // Special case: Menu has no issue, and no letter. This is a menu lacking
            // some information. In that case we do not want to attach all disks to
            // a single menu with no number / no issue. Instead we want to create a new
            // menu for each, so force the search to not return any result so
            // that we create a new menu later on
            $menus = $menus->where('number', '=', -1);
        }

        $menus = $menus->where('version', (trim($stonishMenu->version) !== '') ? trim($stonishMenu->version) : null);
        $menu = null;
        if ($menus->count() === 1) {
            $menu = $menus->first();
            $this->info("\t\tFound menu {$menu->id} {$menu->issue}");
        } else if ($menus->count() < 1) {
            $this->warn("\t\tNo menu found for {$stonishMenu->issue}. Creating a new one");
            $menu = new Menu();
            $menu->number = $stonishMenu->issue;
            // If no issue number but a letter, use this as the menu issue
            if ($stonishMenu->issue === null && $stonishMenu->letter !== null && trim($stonishMenu->letter) !== '') {
                $menu->issue = $stonishMenu->letter;
            }
            $menu->date = ($stonishMenu->date_release !== '0000-00-00') ? $stonishMenu->date_release : null;
            $menu->version = (trim($stonishMenu->version) !== '') ? trim($stonishMenu->version) : null;
            $menuset->menus()->save($menu);
        } else if ($menus->count() > 1) {
            $this->error("\t\tMore than 1 menu found for {$stonishMenu->id_allmenus}");
            $this->error("\t\t" . $menus->pluck('id')->join(', '));
            exit(1);
        }

        return $menu;
    }

    private function getMenuDisk(Menu $menu, object $stonishMenu): MenuDisk
    {
        $disk = new MenuDisk();
        // Only consider using the Stonish menu letter as the the disk part
        // if the menu letter is not already used as the menu issue
        if ($stonishMenu->letter !== null && trim($stonishMenu->letter) !== '' && trim($stonishMenu->letter) !== $menu->issue) {
            $disk->part = trim($stonishMenu->letter);
        }
        $disk->menu_disk_condition_id = $stonishMenu->stateofdisk;

        // Read scrolltext from disk if it exists
        if ($stonishMenu->scrolltext !== null && trim($stonishMenu->scrolltext) !== '') {
            $scrolltextFile = env('STONISH_ROOT') . 'scrolltext/' . $stonishMenu->scrolltext;
            if (file_exists($scrolltextFile)) {
                $scrolltext = file_get_contents($scrolltextFile);
                // Strip off non-printable characters
                $disk->scrolltext = preg_replace('/[[:^print:]]/', '', $scrolltext);
            }
        }
        if ($stonishMenu->comments !== null && trim($stonishMenu->comments) !== '') {
            $disk->notes = trim($stonishMenu->comments);
        }

        $menu->disks()->save($disk);

        if ($stonishMenu->screenshot !== null && trim($stonishMenu->screenshot) !== '') {
            $screenshotFile = env('STONISH_ROOT') . 'screenshot/' . $stonishMenu->screenshot;
            if (file_exists($screenshotFile)) {
                $ext = strtolower(pathinfo($screenshotFile, PATHINFO_EXTENSION));
                $screenshot = new MenuDiskScreenshot();
                $screenshot->imgext = $ext;
                $disk->screenshots()->save($screenshot);

                Storage::disk('public')->put('images/menu_screenshots/' . $screenshot->id . '.' . $ext, file_get_contents($screenshotFile));
            }
        }

        return $disk;
    }

    private function getDump(MenuDisk $disk, object $stonishMenu): ?MenuDiskDump
    {
        if ($stonishMenu->download !== null && trim($stonishMenu->download) !== '') {
            $dumpFile = env('STONISH_ROOT')
                . 'download/' . preg_replace('/\s/', '_', $stonishMenu->name_menus)
                . '/' . $stonishMenu->download;
            if (file_exists($dumpFile)) {
                $this->info("\t\tFound dump {$dumpFile}");

                $dump = new MenuDiskDump();
                $dump->user_id = 4; // ID of Brume

                $zip = new ZipArchive();
                if ($zip->open($dumpFile) === true) {
                    if ($zip->count() !== 1) {
                        $this->error("Unexpected number of files found in ZIP archive {$dumpFile}: {$zip->count()}");
                        exit(1);
                    }

                    $filename = $zip->getNameIndex(0);
                    $ext = strtoupper(pathinfo($filename, PATHINFO_EXTENSION));
                    $dump->format = $ext;

                    $content = $zip->getFromIndex(0);
                    $dump->sha512 = hash('sha512', $content);
                    $dump->size = strlen($content);
                } else {
                    $this->error("Error opening ZIP file {$dumpFile}");
                    exit(1);
                }

                $dump->save();
                $disk->menuDiskDump()->associate($dump);
                $disk->save();
                Storage::disk('public')->put('zips/menus/' . $dump->id . '.zip', file_get_contents($dumpFile));

                return $dump;
            }
        }

        return null;
    }

    public function interrupt()
    {
        $this->info("Interrupting...");
        $this->stop = true;
    }
}
