<?php

namespace App\Console\Commands;

use App\Helpers\ChangelogHelper;
use App\Models\Changelog;
use App\Models\Crew;
use App\Models\Game;
use App\Models\Individual;
use App\Models\IndividualText;
use App\Models\Menu;
use App\Models\MenuDisk;
use App\Models\MenuDiskContent;
use App\Models\MenuDiskDump;
use App\Models\MenuDiskScreenshot;
use App\Models\MenuSet;
use App\Models\MenuSoftware;
use App\Models\Release;
use ErrorException;
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
        if (extension_loaded('pcntl')) {
            pcntl_signal(SIGINT, [$this, 'interrupt']);
        }

        $this->info('Deleting existing release data...');
        $this->withProgressBar(Release::has('menuDiskContents')->get(), function ($release) {
            if ($this->stop) {
                exit(1);
            }

            $release->menuDiskContents->each(function ($content) {
                $content->release()->dissociate();
                $content->save();
            });
            $release->delete();
        });

        DB::table('menu_disk_contents')->delete();
        DB::table('menu_software')->delete();
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
            ->join('people', 'donator', '=', 'people.id_people')
            ->select('allmenus.*', 'namemenus.name_menus', 'people.*')
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

            $crew = $this->getCrew($stonishMenu->name_menus);
            $menuset = $this->getMenuSet($crew, $stonishMenu);
            $menu = $this->getMenu($menuset, $stonishMenu);
            $disk = $this->getMenuDisk($menu, $stonishMenu);
            $dump = $this->getDump($disk, $stonishMenu);

            $contents = $db->table('allcontent')
                ->join('software', 'content', '=', 'software.id_software')
                ->select('allcontent.*', 'software.*')
                ->where('id_menus', '=', $stonishMenu->id_allmenus)
                ->where('id_software', '!=', 0)
                ->orderBy('type')
                ->get();

            foreach ($contents as $content) {
                $this->info("\t\t\t{$content->titlesoft} Type:{$content->typeofsoftware} SubType:{$content->type} AL:{$content->idlegend} DZ:{$content->iddemozoo} Order:{$content->id_number}");
                $menuDiskContent = new MenuDiskContent();
                $menuDiskContent->order = $content->id_number;

                if ($content->version_software !== null && trim($content->version_software) !== '') {
                    $menuDiskContent->version = trim($content->version_software);
                }

                if ($content->requirement !== null && trim($content->requirement) !== '') {
                    $menuDiskContent->requirements = trim($content->requirement);
                }

                if ($content->type !== null && trim($content->type) !== '') {
                    $menuDiskContent->subtype = trim($content->type);
                }

                // AL Game
                if ($content->idlegend) {
                    $game = Game::find($content->idlegend);
                    if ($game !== null) {
                        // Find if there is already a release of this game on the same
                        // disk
                        $release = Release::whereHas('menuDiskContents', function (Builder $query) use ($disk) {
                            $query->where('menu_disk_id', '=', $disk->id);
                        })
                            ->where('game_id', '=', $game->game_id)
                            ->first();

                        // Only create a release if there is not type like "doc" or "trainer",
                        // in which case either it refers to an existing release on this menu,
                        // or a game that is not in the menu (for example Sewer Doc Disks)
                        if (! $release && $content->type === null || trim($content->type) === '') {
                            // Create a new release
                            $release = new Release();
                            $release->type = 'Unofficial';
                            $release->game_id = $game->game_id;
                            if ($menu->date) {
                                $release->date = $menu->date;
                            }
                            $release->save();
                            $this->info("\t\t\t\tCreated new release {$release->id} for Game ID {$content->idlegend}, type {$content->type}");
                        }

                        if ($release) {
                            // Associate release with content
                            $menuDiskContent->game_release_id = $release->id;
                            $this->info("\t\t\t\tAssociated release {$release->id} for Game ID {$content->idlegend}, type {$content->type}");
                        } else {
                            // No release on this menu, but be a doc or trainer for
                            // another game. Associate with the game
                            $menuDiskContent->game_id = $game->game_id;
                            $this->info("\t\t\t\tAssociated Game {$game->game_name} for Game ID {$content->idlegend}, type {$content->type}");
                        }
                    } else {
                        $this->error("Could not find AL game with ID {$content->idlegend} for title {$content->titlesoft}");
                    }
                } else {
                    // Other software
                    $software = $this->getSoftware($content);
                    $menuDiskContent->menu_software_id = $software->id;
                }

                $disk->contents()->save($menuDiskContent);
            }

            $this->info("\t\tImported menu!");
        }

        return 0;
    }

    private function getCrew(string $name): Crew
    {
        $crews = Crew::whereRaw('LOWER(TRIM(crew_name)) = ?', strtolower(trim($name)));
        $crew = null;
        if ($crews->count() === 1) {
            $crew = $crews->first();
            $this->info("\tFound crew {$crew->crew_id} {$crew->crew_name}");
        } elseif ($crews->count() < 1) {
            $this->warn("\tNo crew found with name {$name}. Creating a new one");
            $crew = Crew::create([
                'crew_name' => $name,
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
        } elseif ($crews->count() > 1) {
            $this->error("\tMore than 1 crew found for {$name}");
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
        } elseif ($menusets->count() < 1) {
            $this->warn("\tNo menu set found with name {$menu->name_menus}. Creating a new one");
            $menuset = new MenuSet();
            $menuset->name = $menu->name_menus;
            if ($crew->crew_name === 'Euroswap') {
                // Special case: Euroswap menus are sorted from highest to lowest
                $menuset->menus_sort = 'desc';
            }
            $crew->menuSets()->save($menuset);
        } elseif ($menusets->count() > 2) {
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
        } elseif ($stonishMenu->letter !== null && trim($stonishMenu->letter) !== '') {
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
        } elseif ($menus->count() < 1) {
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
        } elseif ($menus->count() > 1) {
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
        if ($stonishMenu->realname_people !== 'unknown') {
            $disk->donated_by_individual_id = $this->getMainIndividual($stonishMenu)->ind_id;
        }

        // Read scrolltext from disk if it exists
        if ($stonishMenu->scrolltext !== null && trim($stonishMenu->scrolltext) !== '') {
            $scrolltextFile = config('al.stonish.root') . 'scrolltext/' . $stonishMenu->scrolltext;
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
            $screenshotFile = config('al.stonish.root') . 'screenshot/' . $stonishMenu->screenshot;
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

    private function getMainIndividual(object $stonishMenu): Individual
    {
        $individuals = collect([
            ['name' => $stonishMenu->realname_people, 'team' => $stonishMenu->team_people, 'website' => $stonishMenu->website_people],
            ['name' => $stonishMenu->nickname1, 'team' => $stonishMenu->team_people, 'website' => $stonishMenu->website_people],
            ['name' => $stonishMenu->nickname2, 'team' => $stonishMenu->team_people, 'website' => $stonishMenu->website_people],
            ['name' => $stonishMenu->nickname3, 'team' => $stonishMenu->team_people, 'website' => $stonishMenu->website_people],
        ])->filter(function ($individualData) {
            return $individualData['name'] !== null && trim($individualData['name']) !== '';
        })->map(function ($individualData) {
            return $this->getIndividual($individualData);
        });

        $mainIndividual = $individuals->shift();
        $individuals->each(function ($individual) use ($mainIndividual) {
            if (! $mainIndividual->nicknames->pluck('nick_id')->contains($individual->ind_id)) {
                // The Eloquent models for nicknames have changed as of May 2021
                // so the code to handle it doesn't work anymore and has been removed
                // since this command was a once off that will not be used in the future
                throw new ErrorException('Handling of nicknames is not implemented');
            }
        });

        if ($stonishMenu->team_people !== null && trim($stonishMenu->team_people) !== '') {
            $crew = $this->getCrew(trim($stonishMenu->team_people));
            if (! $mainIndividual->crews->pluck('crew_id')->contains($crew->crew_id)) {
                $this->info("\t\tAssociate individual {$mainIndividual->ind_name} with crew {$crew->crew_name}");
                $crew->individuals()->attach($mainIndividual);
            }
        }

        return $mainIndividual;
    }

    private function getIndividual(array $individualData): Individual
    {
        $name = $individualData['name'];
        $individuals = Individual::whereRaw('LOWER(TRIM(ind_name)) = ?', strtolower(trim($name)));
        $individual = null;
        if ($individuals->count() === 1) {
            $individual = $individuals->first();
            $this->info("\t\tFound individual {$individual->ind_id} {$individual->ind_name}");
        } else {
            if ($individuals->count() > 1) {
                $this->error("\t\tMore than 1 individual found for {$name}");
                $this->error("\t\t" . $individuals->pluck('ind_id')->join(', '));
                exit(1);
                // $this->error("\t\tCreating a new one...");
            }

            $individual = new Individual();
            $individual->ind_name = $name;
            $individual->save();

            if ($individualData['website'] !== null && trim($individualData['website']) !== '') {
                $text = new IndividualText();
                $text->ind_profile = '[url]' . trim($individualData['website']) . '[/url]';
                $individual->text()->save($text);
            }

            $this->info("\t\tCreated individual {$individual->ind_name}");
        }

        return $individual;
    }

    private function getDump(MenuDisk $disk, object $stonishMenu): ?MenuDiskDump
    {
        if ($stonishMenu->download !== null && trim($stonishMenu->download) !== '') {
            $dumpFile = config('al.stonish.root')
                . 'download/' . preg_replace('/([^.a-z0-9]+)/i', '_', $stonishMenu->name_menus)
                . '/' . $stonishMenu->download;
            if (file_exists($dumpFile)) {
                $this->info("\t\tFound dump {$dumpFile}");

                $dump = new MenuDiskDump();
                $dump->user_id = 4; // ID of Brume

                $zip = new ZipArchive();
                if ($zip->open($dumpFile) === true) {
                    if ($zip->count() !== 1) {
                        $this->warn("Unexpected number of files found in ZIP archive {$dumpFile}: {$zip->count()}");
                    }

                    $filename = null;
                    $ext = null;

                    // Read each entry in the ZIP and skip files we don't
                    // care about (such as .txt ones)
                    for ($i = 0; $i < $zip->count(); $i++) {
                        $f = $zip->getNameIndex($i);
                        $e = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                        if ($e == 'txt') {
                            continue;
                        }
                        if ($filename || $ext) {
                            $this->error("Already found a candidate file in ZIP archive {$dumpFile}: {$filename}.{$ext} vs. {$f}.{$e}");
                            exit(-1);
                        }
                        $filename = $f;
                        $ext = $e;
                    }
                    if (! $filename || ! $ext) {
                        $this->error("Unable to find dump in ZIP archive {$dumpFile}");
                        exit(-1);
                    }

                    // $filename = $zip->getNameIndex(0);
                    // $ext = strtoupper(pathinfo($filename, PATHINFO_EXTENSION));
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

    private function getSoftware(object $menuContent): MenuSoftware
    {
        $softwares = MenuSoftware::where('name', '=', trim($menuContent->titlesoft));
        $software = null;
        if ($softwares->count() === 1) {
            $software = $softwares->first();
            $this->info("\t\tFound software {$software->name}");
        } elseif ($softwares->count() < 1) {
            $this->info("\t\tNo software for {$menuContent->titlesoft}. Creating a new one");
            $software = new MenuSoftware();
            $software->name = $menuContent->titlesoft;
            if ($menuContent->iddemozoo > 0) {
                $software->demozoo_id = $menuContent->iddemozoo;
            }
            $software->menu_software_content_type_id = $menuContent->typeofsoftware;
            $software->save();
        } else {
            $this->error("\t\tMore than 1 software found for {$menuContent->titlesoft}");
            $this->error("\t\t" . $softwares->pluck('id')->join(', '));
            exit(1);
        }

        return $software;
    }

    public function interrupt()
    {
        $this->info('Interrupting...');
        $this->stop = true;
    }
}
