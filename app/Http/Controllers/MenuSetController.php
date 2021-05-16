<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\MenuDisk;
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

    private function getSortedDisksForSet(MenuSet $set)
    {
        return MenuDisk::select('menu_disks.*')
            ->join('menus', 'menu_id', '=', 'menus.id')
            ->where('menus.menu_set_id', '=', $set->id)
            ->orderBy('number', $set->menus_sort)
            ->orderBy('issue', $set->menus_sort)
            ->orderBy('version')
            ->orderBy('part');
    }

    public function index()
    {

        // Get all sets with their total count of disks, and count of
        // missing disks
        $sets = DB::table('menu_sets')
            ->select('name', 'menu_sets.id')
            ->selectRaw("count('menu_disks.id') as disks")
            ->selectRaw('convert(sum(case when menu_disks.menu_disk_condition_id != ? then 1 else 0 end), unsigned integer) as missing', [
                MenuSetController::INTACT_CONDITION_ID,
            ])
            ->join('menus', 'menus.menu_set_id', 'menu_sets.id')
            ->join('menu_disks', 'menu_disks.menu_id', 'menus.id')
            ->groupBy('menu_sets.id')
            ->orderBy('name')
            ->get();

        return view('menus.index')->with([
            'menusets' => $sets,
        ]);
    }

    public function show(MenuSet $set)
    {
        $disks = $this->getSortedDisksForSet($set)
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

        $randomScreenshot = null;
        $this->getSortedDisksForSet($set)
            ->get()
            ->pluck('screenshots')
            ->flatten()
            ->whenNotEmpty(function ($collection) use (&$randomScreenshot) {
                $randomScreenshot = $collection->random();
            });

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
        $software = MenuSoftware::select();
        $games = Game::select();

        // Boolean to check if a search can be made
        // Search only works via title or titleAZ. If neither
        // are used then there should be no search results in
        // software or games
        $searchPossible = false;

        if ($request->filled('titleAZ')) {
            if ($request->input('titleAZ') === '0-9') {
                $games->where('game_name', 'regexp', '^[0-9]+');
                $software->where('name', 'regexp', '^[0-9]+');
            } else {
                $games->where('game_name', 'like', $request->input('titleAZ').'%');
                $software->where('name', 'like', $request->input('titleAZ').'%');
            }
            $searchPossible = true;
        }

        if ($request->title) {
            $software->where('name', 'like', '%'.$request->title.'%');
            $searchPossible = true;

            $games->where(function (Builder $query) use ($request) {
                $query->where('game_name', 'like', '%'.$request->input('title').'%')
                    ->orWhereHas('akas', function (Builder $subQuery) use ($request) {
                        $subQuery->where('aka_name', 'like', '%'.$request->input('title').'%');
                    });
            });
        }

        if (! $searchPossible) {
            // Force no software results when there were no titles selected
            $software->where('id', '<', 0);
            $games->where('game_id', '<', 0);
        }

        $games->orderBy('game_name')
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
        $book->setTitle('Scrolltexts of '.$set->name);
        $book->setAuthor($set->crews()->pluck('crew_name')->join(', '), '');
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
                $book->addChapter($disk->menu->label.$disk->label, $disk->id.'.xhtml', $content);
                $disk->screenshots
                    ->each(function ($screenshot) use ($book) {
                        $path = 'public/images/menu_screenshots/'.$screenshot->file;
                        $book->addLargeFile(
                            'images/'.$screenshot->file,
                            'screenshot-'.$screenshot->id,
                            Storage::path($path),
                            Storage::mimeType($path)
                        );
                    });
            });

        $book->finalize();

        return response($book->getBook())
            ->header('Content-Type', 'application/epub+zip')
            ->header('Content-Disposition', 'attachment; filename="Scrolltexts of '.$set->name.'.epub"');
    }

    /**
     * Get a cover image for the eBook.
     *
     * @param MenuSet $set Menuset to get a cover for
     *
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
            'by: '.$set->crews->pluck('crew_name')->join(', ')
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
               .' menu disk screenshots, contents and scrolltexts'
        );

        $f = tempnam(sys_get_temp_dir(), 'epub-cover-');
        imagepng($img, $f);

        return $f;
    }
}
