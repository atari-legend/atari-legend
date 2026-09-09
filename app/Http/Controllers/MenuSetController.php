<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\Game;
use App\Models\MenuDisk;
use App\Models\MenuDiskScreenshot;
use App\Models\MenuSet;
use App\Models\MenuSoftware;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use PHPePub\Core\EPub;

class MenuSetController extends Controller
{
    const PAGE_SIZE = 20;

    const SOFTWARE_PAGE_SIZE = 48;

    const INTACT_CONDITION_ID = 4;

    const CONDITION_CLASSES = [
        1 => 'danger',
        2 => 'warning',
        3 => 'warning',
        4 => 'success',
    ];

    /**
     * Everything menus/partial_menudisk and menus/partial_menudisk_content
     * touch on a disk.
     */
    private const DISK_EAGER_LOADS = [
        'menu.menuSet',
        'screenshots',
        'menuDiskCondition',
        'menuDiskDump',
        'donatedByIndividual.games',
        'contents.game',
        'contents.menuSoftware',
        'contents.release.game',
        // The relations ReleaseDescriptionHelper::menuDescriptions() walks.
        'contents.release.languages',
        'contents.release.resolutions',
        'contents.release.systemEnhanced',
        'contents.release.memoryEnhanced',
        'contents.release.memoryMinimums',
        'contents.release.memoryIncompatibles',
        'contents.release.emulatorIncompatibles',
        'contents.release.systemIncompatibles',
        'contents.release.tosIncompatibles',
        'contents.release.copyProtections',
        'contents.release.diskProtections',
        'contents.release.trainers',
    ];

    private function getSortedDisksForSet(MenuSet $set)
    {
        return MenuDisk::select('menu_disks.*')
            ->join('menus', 'menu_id', '=', 'menus.id')
            ->where('menus.menu_set_id', '=', $set->id)
            ->orderBy('number', $set->sort_direction)
            ->orderBy('issue', $set->sort_direction)
            ->orderBy('version')
            ->orderBy('part');
    }

    public function index()
    {
        // Get all sets with their total count of disks, and count of
        // missing disks.
        //
        // The aggregates are cast in PHP rather than in SQL: `convert(...,
        // unsigned integer)` is MySQL-only and the test suite runs against
        // SQLite. The view compares `missing` with `===`, so the cast matters.
        $sets = DB::table('menu_sets')
            ->select('name', 'menu_sets.id')
            ->selectRaw("count('menu_disks.id') as disks")
            ->selectRaw('sum(case when menu_disks.menu_disk_condition_id != ? then 1 else 0 end) as missing', [
                MenuSetController::INTACT_CONDITION_ID,
            ])
            ->join('menus', 'menus.menu_set_id', 'menu_sets.id')
            ->join('menu_disks', 'menu_disks.menu_id', 'menus.id')
            ->groupBy('menu_sets.id', 'menu_sets.name')
            ->orderBy('name')
            ->get()
            ->map(function ($set) {
                $set->disks = (int) $set->disks;
                $set->missing = (int) $set->missing;

                return $set;
            });

        return view('menus.index')->with([
            'menusets' => $sets,
        ]);
    }

    public function show(MenuSet $set)
    {
        // card_show renders the crews and the number of menus, and the page's
        // meta description (MenuHelper::description) walks the disks of every
        // menu in the set. Loading the disks is one query here instead of one
        // per menu; the set of models held in memory is the same either way.
        $set->load(['crews', 'menus.disks']);

        $disks = $this->getSortedDisksForSet($set)
            ->with(MenuSetController::DISK_EAGER_LOADS)
            ->paginate(MenuSetController::PAGE_SIZE);

        $missingDiskCount = DB::table('menu_disks')
            ->join('menus', 'menu_id', '=', 'menus.id')
            ->where('menus.menu_set_id', '=', $set->id)
            ->where('menu_disk_condition_id', '!=', MenuSetController::INTACT_CONDITION_ID)
            ->count();

        $scrollTextCount = DB::table('menu_disks')
            ->join('menus', 'menu_id', '=', 'menus.id')
            ->where('menus.menu_set_id', '=', $set->id)
            ->whereNotNull('scrolltext')
            ->count();

        // Only used for the og:image meta tag. Picking it in SQL keeps this to
        // one query: doing it in PHP meant reading every disk in the set -
        // not just the page being shown - and lazy-loading the screenshots of
        // each one to throw all but a single row away.
        $randomScreenshot = MenuDiskScreenshot::select('menu_disk_screenshots.*')
            ->join('menu_disks', 'menu_disks.id', '=', 'menu_disk_screenshots.menu_disk_id')
            ->join('menus', 'menu_disks.menu_id', '=', 'menus.id')
            ->where('menus.menu_set_id', '=', $set->id)
            ->inRandomOrder()
            ->first();

        return view('menus.show')->with([
            'menuset'          => $set,
            'disks'            => $disks,
            'missingCount'     => $missingDiskCount,
            'scrollTextCount'  => $scrollTextCount,
            'randomScreenshot' => $randomScreenshot,
            'conditionClasses' => MenuSetController::CONDITION_CLASSES,
        ]);
    }

    public function software(MenuSoftware $software)
    {
        $menuDisks = $software->menuDiskContents
            ->map(function ($menuDiskContent) {
                return $menuDiskContent->menuDisk;
            })
            ->unique('id');

        return view('menus.software')->with([
            'software'         => $software,
            'menuDisks'        => $menuDisks,
            'conditionClasses' => MenuSetController::CONDITION_CLASSES,
        ]);
    }

    public function search(Request $request)
    {
        $software = MenuSoftware::select('menu_software.*');
        $games = Game::select('games.*');

        // Boolean to check if a search can be made
        // Search only works via title or titleAZ. If neither
        // are used then there should be no search results in
        // software or games
        $searchPossible = false;

        if ($request->filled('titleAZ')) {
            Helper::whereTitleStartsWith($games, 'games.name', $request->input('titleAZ'));
            Helper::whereTitleStartsWith($software, 'name', $request->input('titleAZ'));
            $searchPossible = true;
        }

        if ($request->title) {
            $software->where('name', 'like', '%' . $request->title . '%');
            $searchPossible = true;

            $games->where(function (Builder $query) use ($request) {
                $query->where('games.name', 'like', '%' . $request->input('title') . '%')
                    ->orWhereHas('akas', function (Builder $subQuery) use ($request) {
                        $subQuery->where('name', 'like', '%' . $request->input('title') . '%');
                    });
            });
        }

        if (! $searchPossible) {
            // Force no software results when there were no titles selected
            $software->where('id', '<', 0);
            $games->where('games.id', '<', 0);
        }

        $games->orderBy('games.name')
            ->paginate(GameSearchController::PAGE_SIZE);

        $software->orderBy('name')
            ->paginate(MenuSetController::PAGE_SIZE);

        return view('menus.search')->with([
            'software' => $software->paginate(48),
            'games'    => $games->paginate(48),
            'title'    => $request->title,
            'titleAZ'  => $request->titleAZ,
        ]);
    }

    public function epub(MenuSet $set)
    {
        $book = new EPub(EPub::BOOK_VERSION_EPUB3);
        $book->setTitle('Scrolltexts of ' . $set->name);
        $book->setAuthor($set->crews()->pluck('name')->join(', '), '');
        $book->setPublisher('Atari Legend', URL::to('/'));
        $book->setSourceURL(route('menus.show', $set));
        $book->addCSSFile('epub.css', 'epub.css', file_get_contents(base_path('resources/css/epub.css')));
        $book->addLargeFile('images/demozoo.png', 'demozoo', base_path('public/images/demozoo-16x16.png'), 'image/png');
        $book->addLargeFile('fonts/RobotoMono-Regular.otf', 'font.RobotoMono-Regular.regular', base_path('resources/fonts/RobotoMono-Regular.otf'), 'font/opentype');
        $book->setCoverImage('cover.png', file_get_contents($this->getEpubCover($set)), 'image/png');

        $book->addChapter('Cover', 'cover.xhtml', view('menus.epub.cover', ['set' => $set])->render());

        $this->getSortedDisksForSet($set)
            ->each(function ($disk) use ($book) {
                $content = view('menus.epub.disk', ['disk' => $disk])->render();
                $book->addChapter($disk->menu->label . $disk->label, $disk->id . '.xhtml', $content);
                $disk->screenshots
                    ->each(function ($screenshot) use ($book) {
                        $path = 'public/images/menu_screenshots/' . $screenshot->file;
                        $book->addLargeFile(
                            'images/' . $screenshot->file,
                            'screenshot-' . $screenshot->id,
                            Storage::path($path),
                            Storage::mimeType($path)
                        );
                    });
            });

        $book->finalize();

        return response($book->getBook())
            ->header('Content-Type', 'application/epub+zip')
            ->header('Content-Disposition', 'attachment; filename="Scrolltexts of ' . $set->name . '.epub"');
    }

    /**
     * Get a cover image for the eBook.
     *
     * @param  MenuSet  $set  Menuset to get a cover for
     * @return string Path to temporarily file containing the cover image
     */
    private function getEpubCover(MenuSet $set): string
    {
        // Read base cover image and prepare things
        $img = imagecreatefrompng(base_path('public/images/epub-cover.png'));
        $white = imagecolorallocate($img, 255, 255, 255);
        $grey = imagecolorallocate($img, 204, 204, 204);
        $font = base_path('vendor/webfontkit/roboto/fonts/roboto-regular.ttf');

        // The title may be too long to fit in a single line. Split it
        // by columns of 20 characters max, respecting the word boundaries
        $lines = explode("\n", wordwrap($set->name, 20, "\n"));

        // Height of a line of title text, in pixels
        $lineHeight = 72;

        // Print each title line
        $i = 0;
        for (; $i < count($lines); $i++) {
            imagettftext(
                $img,
                48,
                0,
                60,
                300 + $i * $lineHeight,
                $white,
                $font,
                $lines[$i]
            );
        }

        // Print subtitle: Crew names
        imagettftext(
            $img,
            30,
            0,
            60,
            320 + $i * $lineHeight,
            $grey,
            $font,
            'by: ' . $set->crews->pluck('name')->join(', ')
        );

        // Print generic text at the bottom
        imagettftext(
            $img,
            20,
            0,
            60,
            680,
            $grey,
            $font,
            $set->menus->pluck('disks')->flatten()->count()
               . ' menu disk screenshots, contents and scrolltexts'
        );

        $f = tempnam(sys_get_temp_dir(), 'epub-cover-');
        imagepng($img, $f);

        return $f;
    }
}
