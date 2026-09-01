<?php

namespace App\Livewire\Admin;

use App\Helpers\ChangelogHelper;
use App\Helpers\MenuImportParser;
use App\Models\Changelog;
use App\Models\Game;
use App\Models\GameAka;
use App\Models\GameRelease;
use App\Models\Individual;
use App\Models\Menu;
use App\Models\MenuDisk;
use App\Models\MenuDiskCondition;
use App\Models\MenuDiskContent;
use App\Models\MenuSet;
use App\Models\MenuSoftware;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Bulk import of menus / disks / disk-contents into a menu set.
 *
 * Workflow:
 *   1. The user uploads a filled-in copy of the Excel template.
 *   2. {@see MenuImportParser} turns it into a nested menus → disks → contents
 *      array, which we resolve against the database (matching games, software,
 *      conditions, donators) and annotate with a per-row link mode + warnings.
 *   3. The user reviews and corrects everything on screen. Nothing is written
 *      until they press "Import", at which point {@see self::runImport()} replays
 *      the exact same logic as MenuDisksContentController::store() inside a
 *      single transaction.
 */
class MenuImport extends Component
{
    use WithFileUploads;

    public MenuSet $set;

    public $file;

    /** Parsed + resolved + user-edited structure. @var array<int, array<string, mixed>> */
    public array $menus = [];

    public bool $reviewing = false;

    /**
     * Set when the user tries to import while problems remain. The notice is
     * rendered from the live error count and hides itself once that reaches
     * zero, so it never carries a stale count.
     */
    public bool $importAttempted = false;

    /** Link modes a content row can take. */
    public const LINK_MODES = [
        'new_release' => 'New game release',
        'extra'       => 'Extra of a release on this menu',
        'standalone'  => 'Standalone game doc / extra',
        'software'    => 'Software',
    ];

    protected function rules(): array
    {
        return [
            // `extensions` checks the client extension, which is reliable for
            // xlsx files (their MIME type is often detected as application/zip).
            'file' => 'required|file|extensions:xlsx,xls',
        ];
    }

    public function updatedFile(): void
    {
        $this->validate();

        $parsed = MenuImportParser::parseFile($this->file->getRealPath());
        $this->menus = $this->resolveInitial($parsed);
        $this->reviewing = true;

        $this->dispatch('menu-import-rendered');
    }

    public function startOver(): void
    {
        $this->reset(['file', 'menus', 'reviewing', 'importAttempted']);
    }

    /**
     * Drop a disk from the review, so a disk whose data is wrong can be left
     * out while the rest of the sheet is still imported.
     *
     * The remaining disks deliberately keep their original keys instead of
     * being re-indexed: the review screen's autocomplete inputs are plain
     * (non-wire:model) fields addressed by their menu/disk/content index, and
     * shifting those indices would leave them pointing at a different row.
     */
    public function removeDisk(int $mi, int $di): void
    {
        if (! isset($this->menus[$mi]['disks'][$di])) {
            return;
        }

        unset($this->menus[$mi]['disks'][$di]);

        // A menu with no disks left would be imported as an empty shell.
        if (count($this->menus[$mi]['disks']) === 0) {
            unset($this->menus[$mi]);
        }

        if (count($this->menus) === 0) {
            $this->startOver();

            return;
        }

        // Rows disappeared from the DOM: re-register the autocompletes so the
        // ones built for them are disposed of.
        $this->dispatch('menu-import-rendered');
    }

    /**
     * Drop a single content row, closing the gap it leaves in the disk's `order`
     * sequence. Keys are preserved rather than re-indexed for the same reason as
     * in {@see self::removeDisk()}.
     *
     * A disk left with no contents is kept: unlike an empty menu, it carries its
     * own part / condition / donator / notes, and a content-free disk is a
     * perfectly normal record. Use "Don't import this disk" to drop it.
     */
    public function removeContent(int $mi, int $di, int $ci): void
    {
        if (! isset($this->menus[$mi]['disks'][$di]['contents'][$ci])) {
            return;
        }

        unset($this->menus[$mi]['disks'][$di]['contents'][$ci]);

        $this->renumberDisk($mi, $di);

        $this->dispatch('menu-import-rendered');
    }

    /**
     * Renumber a disk's contents 1..N, following the order in which they are
     * listed (which is the order they appear in on screen, and the order they
     * came in from the sheet) rather than their current `order` values. Flattens
     * out both the gap a deleted row leaves behind and any odd numbering the
     * spreadsheet came with.
     */
    public function renumberDisk(int $mi, int $di): void
    {
        if (! isset($this->menus[$mi]['disks'][$di])) {
            return;
        }

        $order = 1;
        foreach ($this->menus[$mi]['disks'][$di]['contents'] as $ci => $content) {
            $this->menus[$mi]['disks'][$di]['contents'][$ci]['order'] = $order++;
        }
    }

    // -----------------------------------------------------------------
    //  Resolution
    // -----------------------------------------------------------------

    /**
     * First-pass resolution run once, right after upload: auto-match every
     * entity by name and pick a link mode by heuristic.
     */
    private function resolveInitial(array $parsedMenus): array
    {
        $menus = [];

        foreach ($parsedMenus as $mi => $menu) {
            $resolvedDisks = [];

            // Match this menu against an existing one in the set first, so each
            // disk can be matched against that menu's existing disks.
            $existingMenu = $this->matchExistingMenu($menu['number'], $menu['issue']);

            foreach ($menu['disks'] as $di => $disk) {
                $contents = [];
                foreach ($disk['contents'] as $ci => $content) {
                    $contents[] = $this->resolveContent($content);
                }

                $existingDisk = $existingMenu
                    ? $this->matchExistingDisk($existingMenu->id, $disk['part'])
                    : null;

                $resolvedDisks[] = [
                    'part'                => $disk['part'],
                    'condition'           => $disk['condition'],
                    'condition_id'        => $this->matchCondition($disk['condition']),
                    'donated_by'          => $disk['donated_by'],
                    'donated_by_id'       => $this->matchIndividual($disk['donated_by']),
                    'notes'               => $disk['notes'],
                    'scrolltext'          => $disk['scrolltext'],
                    'existing_disk_id'    => $existingDisk?->id,
                    'existing_disk_label' => $existingDisk?->label,
                    'contents'            => $contents,
                ];
            }

            // Find all game IDs that are main releases (type Game, empty sub-type)
            // on this menu so we can distinguish extras vs standalones in memory.
            $mainReleaseGameIds = [];
            foreach ($resolvedDisks as $disk) {
                foreach ($disk['contents'] as $content) {
                    if ($content['link_mode'] !== 'software' && empty($content['subtype']) && ! empty($content['game_id'])) {
                        $mainReleaseGameIds[] = $content['game_id'];
                    }
                }
            }
            $mainReleaseGameIds = array_values(array_unique($mainReleaseGameIds));

            // Adjust link modes for game contents based on presence of a main release
            foreach ($resolvedDisks as &$disk) {
                foreach ($disk['contents'] as &$content) {
                    if ($content['link_mode'] !== 'software') {
                        $hasSubtype = $content['subtype'] !== null && $content['subtype'] !== '';
                        if (! $hasSubtype) {
                            $content['link_mode'] = 'new_release';
                        } elseif (! empty($content['game_id']) && in_array($content['game_id'], $mainReleaseGameIds, true)) {
                            $content['link_mode'] = 'extra';
                        } else {
                            $content['link_mode'] = 'standalone';
                        }
                    }
                }
                unset($content);
            }
            unset($disk);

            $menus[] = [
                'number'              => $menu['number'],
                'issue'               => $menu['issue'],
                'version'             => $menu['version'],
                'date'                => $this->normaliseDate($menu['date']),
                'existing_menu_id'    => $existingMenu?->id,
                'existing_menu_label' => $existingMenu?->label,
                'disks'               => $resolvedDisks,
            ];
        }

        return $menus;
    }

    /**
     * Resolve a single parsed content row into the editable/annotated shape.
     */
    private function resolveContent(array $content): array
    {
        $isSoftware = strtolower((string) $content['type']) === 'software';

        $resolved = [
            'row'          => $content['row'],
            'order'        => $content['order'],
            // `name` is the immutable original from the sheet (for the "From
            // sheet" hint); `query` is the current search term (what the user
            // typed / the resolved name), used for the value + error wording.
            'name'          => $content['name'],
            'query'         => $content['name'],
            'subtype'       => $content['subtype'],
            'version'       => $content['version'],
            'requirements'  => $content['requirements'],
            'game_id'       => null,
            'game_name'     => null,
            'software_id'   => null,
            'software_name' => null,
            'candidates'    => [],
        ];

        if ($isSoftware) {
            [$id, $name, $candidates] = $this->matchSoftware($content['name']);
            $resolved['software_id'] = $id;
            $resolved['software_name'] = $name;
            $resolved['candidates'] = $candidates;
            $resolved['link_mode'] = 'software';

            return $resolved;
        }

        [$id, $name, $candidates] = $this->matchGame($content['name']);
        $resolved['game_id'] = $id;
        $resolved['game_name'] = $name;
        $resolved['candidates'] = $candidates;
        $resolved['link_mode'] = 'new_release'; // Default, adjusted in resolveInitial

        return $resolved;
    }

    /**
     * @return array{0: ?int, 1: ?string, 2: array<int, array{id:int, name:string}>}
     */
    private function matchGame(?string $name): array
    {
        if ($name === null || $name === '') {
            return [null, null, []];
        }

        $games = Game::where('name', $name)->get(['id', 'name']);
        $akas = GameAka::where('name', $name)->get(['game_id', 'name']);

        $candidates = collect();
        foreach ($games as $game) {
            $candidates->push(['id' => $game->getKey(), 'name' => $game->name]);
        }
        foreach ($akas as $aka) {
            // game_id is GameAka's foreign key to the game this alias belongs to,
            // not GameAka's own primary key: the candidate list identifies games.
            $candidates->push(['id' => $aka->game_id, 'name' => $aka->name . ' (AKA)']);
        }
        $candidates = $candidates->unique('id')->values();

        if ($candidates->count() === 1) {
            return [$candidates[0]['id'], $candidates[0]['name'], []];
        }

        return [null, null, $candidates->all()];
    }

    /**
     * @return array{0: ?int, 1: ?string, 2: array<int, array{id:int, name:string}>}
     */
    private function matchSoftware(?string $name): array
    {
        if ($name === null || $name === '') {
            return [null, null, []];
        }

        $matches = MenuSoftware::where('name', $name)->get(['id', 'name']);

        if ($matches->count() === 1) {
            return [$matches[0]->id, $matches[0]->name, []];
        }

        $candidates = $matches->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->all();

        return [null, null, $candidates];
    }

    private function matchCondition(?string $name): ?int
    {
        if ($name === null || $name === '') {
            return null;
        }

        return MenuDiskCondition::where('name', $name)->value('id');
    }

    private function matchIndividual(?string $name): ?int
    {
        if ($name === null || $name === '') {
            return null;
        }

        // Try the name as-is first (so a real name that happens to contain
        // brackets still wins).
        $id = Individual::where('ind_name', $name)->value('id');
        if ($id) {
            return $id;
        }

        // The individuals autocomplete decorates its results with "(aka: …)"
        // and "[games…]" suffixes (see Ajax\IndividualController). When the user
        // picks a suggestion, that decorated label lands in the field, so fall
        // back to matching the undecorated name.
        $stripped = trim(preg_replace('/\s*(?:\(aka:.*|\[[^\]]*\])\s*$/', '', $name));
        if ($stripped !== '' && $stripped !== $name) {
            return Individual::where('ind_name', $stripped)->value('id');
        }

        return null;
    }

    private function normaliseDate(?string $date): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }

        $date = trim($date);

        // If it's a 4-digit year, default to January 1st of that year.
        if (preg_match('/^\d{4}$/', $date)) {
            return $date . '-01-01';
        }

        // If it's YYYY-MM, default to the 1st of that month.
        if (preg_match('/^\d{4}-\d{2}$/', $date)) {
            return $date . '-01';
        }

        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    // -----------------------------------------------------------------
    //  Interactive edits (called from the review screen)
    // -----------------------------------------------------------------

    public function updateGame(int $mi, int $di, int $ci, $gameId): void
    {
        $gameId = $gameId !== '' && $gameId !== null ? (int) $gameId : null;
        $game = $gameId ? Game::find($gameId) : null;

        $this->menus[$mi]['disks'][$di]['contents'][$ci]['game_id'] = $game?->getKey();
        $this->menus[$mi]['disks'][$di]['contents'][$ci]['game_name'] = $game?->name;
        $this->menus[$mi]['disks'][$di]['contents'][$ci]['query'] = $game?->name;
        $this->menus[$mi]['disks'][$di]['contents'][$ci]['candidates'] = [];
    }

    public function updateSoftware(int $mi, int $di, int $ci, $softwareId): void
    {
        $softwareId = $softwareId !== '' && $softwareId !== null ? (int) $softwareId : null;
        $software = $softwareId ? MenuSoftware::find($softwareId) : null;

        $this->menus[$mi]['disks'][$di]['contents'][$ci]['software_id'] = $software?->id;
        $this->menus[$mi]['disks'][$di]['contents'][$ci]['software_name'] = $software?->name;
        $this->menus[$mi]['disks'][$di]['contents'][$ci]['query'] = $software?->name;
        $this->menus[$mi]['disks'][$di]['contents'][$ci]['candidates'] = [];
    }

    /**
     * Free-typed game name (the field was edited without choosing a suggestion).
     * Re-resolve it so a string that matches no game is flagged, while a real
     * autocomplete pick (whose decorated label still matches the linked game) is
     * left untouched.
     */
    public function setGameSearch(int $mi, int $di, int $ci, ?string $text): void
    {
        $content = $this->menus[$mi]['disks'][$di]['contents'][$ci];
        $stripped = $this->stripDecoration($text);

        if (! empty($content['game_id']) && $stripped !== null && $stripped === $content['game_name']) {
            return;
        }

        [$id, $name, $candidates] = $this->matchGame($stripped);
        $this->menus[$mi]['disks'][$di]['contents'][$ci]['game_id'] = $id;
        $this->menus[$mi]['disks'][$di]['contents'][$ci]['game_name'] = $name;
        $this->menus[$mi]['disks'][$di]['contents'][$ci]['query'] = $stripped;
        $this->menus[$mi]['disks'][$di]['contents'][$ci]['candidates'] = $candidates;
    }

    /** Free-typed software name — same idea as {@see self::setGameSearch()}. */
    public function setSoftwareSearch(int $mi, int $di, int $ci, ?string $text): void
    {
        $content = $this->menus[$mi]['disks'][$di]['contents'][$ci];
        $text = $text !== null && trim($text) !== '' ? trim($text) : null;

        if (! empty($content['software_id']) && $text !== null && $text === $content['software_name']) {
            return;
        }

        [$id, $name, $candidates] = $this->matchSoftware($text);
        $this->menus[$mi]['disks'][$di]['contents'][$ci]['software_id'] = $id;
        $this->menus[$mi]['disks'][$di]['contents'][$ci]['software_name'] = $name;
        $this->menus[$mi]['disks'][$di]['contents'][$ci]['query'] = $text;
        $this->menus[$mi]['disks'][$di]['contents'][$ci]['candidates'] = $candidates;
    }

    /** Strip the " [developers]" suffix the games autocomplete appends. */
    private function stripDecoration(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        $text = trim(preg_replace('/\s*\[[^\]]*\]\s*$/', '', $text));

        return $text === '' ? null : $text;
    }

    /** Pick an individual from the autocomplete (precise, by id). */
    public function updateIndividual(int $mi, int $di, $individualId): void
    {
        $individualId = $individualId !== '' && $individualId !== null ? (int) $individualId : null;
        $individual = $individualId ? Individual::find($individualId) : null;

        $this->menus[$mi]['disks'][$di]['donated_by_id'] = $individual?->getKey();
        $this->menus[$mi]['disks'][$di]['donated_by'] = $individual?->ind_name;
    }

    /** Free-typed donator name (no autocomplete selection) — must resolve. */
    public function setDonatedBy(int $mi, int $di, ?string $text): void
    {
        $text = $text !== null && trim($text) !== '' ? trim($text) : null;

        $stripped = $text;
        if ($text !== null) {
            $stripped = trim(preg_replace('/\s*(?:\(aka:.*|\[[^\]]*\])\s*$/', '', $text));
        }

        $disk = $this->menus[$mi]['disks'][$di];
        if (! empty($disk['donated_by_id']) && $stripped !== null && $stripped === $disk['donated_by']) {
            return;
        }

        $this->menus[$mi]['disks'][$di]['donated_by'] = $stripped;
        $this->menus[$mi]['disks'][$di]['donated_by_id'] = $this->matchIndividual($stripped);
    }

    public function clearIndividual(int $mi, int $di): void
    {
        $this->menus[$mi]['disks'][$di]['donated_by'] = null;
        $this->menus[$mi]['disks'][$di]['donated_by_id'] = null;
    }

    /** Re-evaluate cheap derived state when a bound field changes. */
    public function updated(string $name): void
    {
        if (! $this->reviewing) {
            return;
        }

        // Disk condition name → id.
        if (preg_match('/^menus\.(\d+)\.disks\.(\d+)\.condition$/', $name, $m)) {
            $this->menus[$m[1]]['disks'][$m[2]]['condition_id'] =
                $this->matchCondition($this->menus[$m[1]]['disks'][$m[2]]['condition']);
        }

        // Menu number / issue → existing-menu match. The candidate disk set
        // changes with the menu match, so re-match every disk of this menu too.
        if (preg_match('/^menus\.(\d+)\.(number|issue)$/', $name, $m)) {
            $mi = (int) $m[1];
            $existingMenu = $this->matchExistingMenu($this->menus[$mi]['number'], $this->menus[$mi]['issue']);
            $this->menus[$mi]['existing_menu_id'] = $existingMenu?->id;
            $this->menus[$mi]['existing_menu_label'] = $existingMenu?->label;

            foreach ($this->menus[$mi]['disks'] as $di => $disk) {
                $existingDisk = $existingMenu
                    ? $this->matchExistingDisk($existingMenu->id, $disk['part'])
                    : null;
                $this->menus[$mi]['disks'][$di]['existing_disk_id'] = $existingDisk?->id;
                $this->menus[$mi]['disks'][$di]['existing_disk_label'] = $existingDisk?->label;
            }
        }

        // Disk part → existing-disk match (only meaningful when the menu matched).
        if (preg_match('/^menus\.(\d+)\.disks\.(\d+)\.part$/', $name, $m)) {
            $mi = (int) $m[1];
            $di = (int) $m[2];
            $existingMenuId = $this->menus[$mi]['existing_menu_id'] ?? null;
            $existingDisk = $existingMenuId
                ? $this->matchExistingDisk($existingMenuId, $this->menus[$mi]['disks'][$di]['part'])
                : null;
            $this->menus[$mi]['disks'][$di]['existing_disk_id'] = $existingDisk?->id;
            $this->menus[$mi]['disks'][$di]['existing_disk_label'] = $existingDisk?->label;
        }

        // Switching link mode swaps the game/software autocomplete picker, so
        // the freshly rendered input must be (re-)initialised.
        if (preg_match('/^menus\.\d+\.disks\.\d+\.contents\.\d+\.link_mode$/', $name)) {
            $this->dispatch('menu-import-rendered');
        }
    }

    // -----------------------------------------------------------------
    //  Validation
    // -----------------------------------------------------------------

    /**
     * Find an existing menu in this set to merge into, matched on number +
     * issue. A menu with both fields blank never matches (too ambiguous —
     * mirrors the blank-`part` rule for disks). The lowest id wins when several
     * match (deterministic; the table has no unique constraint).
     */
    private function matchExistingMenu(?string $number, ?string $issue): ?Menu
    {
        $number = $number === '' ? null : $number;
        $issue = $issue === '' ? null : $issue;

        if ($number === null && $issue === null) {
            return null;
        }

        $query = $this->set->menus();
        $number === null ? $query->whereNull('number') : $query->where('number', $number);
        $issue === null ? $query->whereNull('issue') : $query->where('issue', $issue);

        return $query->orderBy('id')->first();
    }

    /**
     * Find an existing disk on the given menu to merge into, matched on `part`
     * (trimmed, case-insensitive). A part-less imported disk matches a part-less
     * existing disk — the common single-disk-menu case, where neither side names
     * a part. The lowest id wins when several match.
     */
    private function matchExistingDisk(int $menuId, ?string $part): ?MenuDisk
    {
        $part = $part === null ? '' : trim($part);

        $query = MenuDisk::where('menu_id', $menuId);

        if ($part === '') {
            $query->where(function ($q) {
                $q->whereNull('part')->orWhereRaw("TRIM(part) = ''");
            });
        } else {
            $query->whereRaw('LOWER(TRIM(part)) = ?', [strtolower($part)]);
        }

        return $query->orderBy('id')->first();
    }

    /**
     * Blocking errors for a single content row, given the game ids that are main
     * releases on its menu. Returns a list of human-readable messages.
     *
     * @return array<int, string>
     */
    public function contentErrors(array $content, array $mainReleaseGameIds): array
    {
        $errors = [];

        if ($content['order'] === null || $content['order'] === '' || ! is_numeric($content['order'])) {
            $errors[] = 'Order must be a whole number.';
        }

        $mode = $content['link_mode'];

        if ($mode === 'software') {
            if (empty($content['software_id'])) {
                $errors[] = $this->matchErrorMessage($content, 'software');
            }

            return $errors;
        }

        // Game-based modes.
        if (empty($content['game_id'])) {
            $errors[] = $this->matchErrorMessage($content, 'game');
        }

        if ($mode === 'extra') {
            if ($content['subtype'] === null || $content['subtype'] === '') {
                $errors[] = 'An extra needs a sub-type (e.g. "Docs").';
            }
            if (! empty($content['game_id']) && ! in_array($content['game_id'], $mainReleaseGameIds, true)) {
                $errors[] = 'This game has no main release on this menu — change the link mode.';
            }
        }

        if ($mode === 'standalone') {
            if ($content['subtype'] === null || $content['subtype'] === '') {
                $errors[] = 'A standalone game doc / extra needs a sub-type.';
            }
        }

        return $errors;
    }

    /**
     * The single message shown when a game / software row is unresolved: either
     * "several matches, pick one" (ambiguous) or "not found" (no match at all).
     */
    private function matchErrorMessage(array $content, string $kind): string
    {
        if (! empty($content['candidates'])) {
            $names = collect($content['candidates'])->pluck('name')->take(5)->join(', ');

            return "Several matches found — pick the right one: {$names}";
        }

        $entity = $kind === 'software' ? 'software entry' : 'game';
        $label = $kind === 'software' ? 'Software' : 'Game';

        return ! empty($content['query'])
            ? "{$label} \"{$content['query']}\" was not found — pick the matching {$entity}."
            : "Choose a {$entity}.";
    }

    /** Game ids that are main releases on the menu at the given index (for the view). */
    public function menuMainReleaseGameIds(int $mi): array
    {
        return $this->resolvedMainReleaseGameIds($this->menus[$mi]);
    }

    /** Game ids that are a "new_release" on a (resolved) menu. */
    private function resolvedMainReleaseGameIds(array $menu): array
    {
        $ids = [];
        foreach ($menu['disks'] as $disk) {
            foreach ($disk['contents'] as $content) {
                if ($content['link_mode'] === 'new_release' && ! empty($content['game_id'])) {
                    $ids[] = $content['game_id'];
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /** Total number of blocking problems across the whole import. */
    public function getErrorCount(): int
    {
        $count = 0;

        foreach ($this->menus as $menu) {
            $mainReleaseGameIds = $this->resolvedMainReleaseGameIds($menu);

            foreach ($menu['disks'] as $disk) {
                // A condition is required only for newly created disks; merged
                // disks keep their existing condition.
                if (empty($disk['existing_disk_id']) && empty($disk['condition_id'])) {
                    $count++;
                }
                // A donator name that resolves to no individual is a problem.
                if (! empty($disk['donated_by']) && empty($disk['donated_by_id'])) {
                    $count++;
                }
                foreach ($disk['contents'] as $content) {
                    $count += count($this->contentErrors($content, $mainReleaseGameIds));
                }
            }
        }

        return $count;
    }

    // -----------------------------------------------------------------
    //  Commit
    // -----------------------------------------------------------------

    public function runImport()
    {
        if ($this->getErrorCount() > 0) {
            $this->importAttempted = true;
            $this->dispatch('menu-import-has-errors');

            return null;
        }

        DB::transaction(function () {
            foreach ($this->menus as $menu) {
                $this->commitMenu($menu);
            }
        });

        session()->flash('alert-success', 'The menus were imported successfully.');

        return redirect()->route('admin.menus.sets.edit', $this->set);
    }

    /**
     * Fill the given columns on a model only where its current value is
     * null/empty — never overwriting a value that's already set. Saves and
     * returns true if anything changed.
     */
    private function fillBlanks(\Illuminate\Database\Eloquent\Model $model, array $values): bool
    {
        foreach ($values as $column => $value) {
            $value = $value === '' ? null : $value;
            if ($value !== null && ($model->{$column} === null || $model->{$column} === '')) {
                $model->{$column} = $value;
            }
        }

        if ($model->isDirty()) {
            $model->save();

            return true;
        }

        return false;
    }

    private function commitMenu(array $menu): void
    {
        if (! empty($menu['existing_menu_id'])) {
            // Merge into the existing menu: fill only blank metadata, keep
            // everything already set. number/issue are match keys, never filled.
            $menuModel = Menu::find($menu['existing_menu_id']);
            $changed = $this->fillBlanks($menuModel, [
                'version' => $menu['version'] ?: null,
                'date'    => $menu['date'] ?: null,
            ]);

            if ($changed) {
                ChangelogHelper::insert([
                    'action'           => Changelog::UPDATE,
                    'section'          => 'Menus',
                    'section_id'       => $menuModel->getKey(),
                    'section_name'     => $menuModel->full_label,
                    'sub_section'      => 'Menu',
                    'sub_section_id'   => $menuModel->getKey(),
                    'sub_section_name' => $menuModel->full_label,
                ]);
            }
        } else {
            $menuModel = Menu::create([
                'number'      => $menu['number'] ?: null,
                'issue'       => $menu['issue'] ?: null,
                'version'     => $menu['version'] ?: null,
                'date'        => $menu['date'] ?: null,
                'menu_set_id' => $this->set->id,
            ]);

            ChangelogHelper::insert([
                'action'           => Changelog::INSERT,
                'section'          => 'Menus',
                'section_id'       => $menuModel->getKey(),
                'section_name'     => $menuModel->full_label,
                'sub_section'      => 'Menu',
                'sub_section_id'   => $menuModel->getKey(),
                'sub_section_name' => $menuModel->full_label,
            ]);
        }

        // Resolve every disk up-front (creating new ones, merging into existing
        // ones), keyed by index, then create contents in two passes so an
        // "extra" can attach to a release created on any disk of the same menu.
        $diskModels = [];
        foreach ($menu['disks'] as $di => $disk) {
            if (! empty($disk['existing_disk_id'])) {
                $diskModel = MenuDisk::find($disk['existing_disk_id']);
                $changed = $this->fillBlanks($diskModel, [
                    'menu_disk_condition_id'   => $disk['condition_id'],
                    'donated_by_individual_id' => $disk['donated_by_id'],
                    'notes'                    => $disk['notes'] ?: null,
                    'scrolltext'               => $disk['scrolltext'] ?: null,
                ]);

                if ($changed) {
                    ChangelogHelper::insert([
                        'action'           => Changelog::UPDATE,
                        'section'          => 'Menu Disks',
                        'section_id'       => $diskModel->getKey(),
                        'section_name'     => $diskModel->download_basename,
                        'sub_section'      => 'Disk',
                        'sub_section_id'   => $diskModel->getKey(),
                        'sub_section_name' => $diskModel->download_basename,
                    ]);
                }
            } else {
                $diskModel = MenuDisk::create([
                    'part'                     => $disk['part'],
                    'notes'                    => $disk['notes'],
                    'scrolltext'               => $disk['scrolltext'],
                    'menu_disk_condition_id'   => $disk['condition_id'],
                    'donated_by_individual_id' => $disk['donated_by_id'],
                    'menu_id'                  => $menuModel->id,
                ]);

                ChangelogHelper::insert([
                    'action'           => Changelog::INSERT,
                    'section'          => 'Menu Disks',
                    'section_id'       => $diskModel->getKey(),
                    'section_name'     => $diskModel->download_basename,
                    'sub_section'      => 'Disk',
                    'sub_section_id'   => $diskModel->getKey(),
                    'sub_section_name' => $diskModel->download_basename,
                ]);
            }

            $diskModels[$di] = $diskModel;
        }

        // Pass 1: everything except extras (extras need their release to exist).
        // Extras only ever link to releases created in *this* run; that's safe
        // because merged disks are content-free by premise, so any release an
        // extra wants is created here in pass 1.
        $releasesByGame = [];
        foreach ($menu['disks'] as $di => $disk) {
            foreach ($disk['contents'] as $content) {
                if ($content['link_mode'] === 'extra') {
                    continue;
                }
                $release = $this->commitContent($content, $diskModels[$di]);
                if ($content['link_mode'] === 'new_release' && $release) {
                    $releasesByGame[$content['game_id']] = $release;
                }
            }
        }

        // Pass 2: extras, attached to the release created for the same game.
        foreach ($menu['disks'] as $di => $disk) {
            foreach ($disk['contents'] as $content) {
                if ($content['link_mode'] !== 'extra') {
                    continue;
                }
                $this->commitContent($content, $diskModels[$di], $releasesByGame[$content['game_id']] ?? null);
            }
        }
    }

    /**
     * Create one MenuDiskContent and link it, mirroring
     * MenuDisksContentController::store(). Returns the Release it created/used
     * (for new_release rows) so extras can be attached to it.
     */
    private function commitContent(array $content, MenuDisk $disk, ?GameRelease $extraRelease = null): ?GameRelease
    {
        $model = MenuDiskContent::create([
            'order'        => $content['order'],
            'subtype'      => $content['subtype'] ?: null,
            'version'      => $content['version'] ?: null,
            'requirements' => $content['requirements'] ?: null,
            'menu_disk_id' => $disk->id,
        ]);

        $release = null;

        switch ($content['link_mode']) {
            case 'new_release':
                $release = GameRelease::create([
                    'type'    => GameRelease::TYPE_UNOFFICIAL,
                    'game_id' => $content['game_id'],
                ]);
                $release->menuDiskContents()->save($model);
                break;

            case 'extra':
                $extraRelease?->menuDiskContents()->save($model);
                break;

            case 'standalone':
                Game::find($content['game_id'])->menuDiskContents()->save($model);
                break;

            case 'software':
                MenuSoftware::find($content['software_id'])->menuDiskContents()->save($model);
                break;
        }

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Menu Disks',
            'section_id'       => $disk->getKey(),
            'section_name'     => $disk->label,
            'sub_section'      => 'Content',
            'sub_section_id'   => $model->getKey(),
            'sub_section_name' => $model->label,
        ]);

        return $release;
    }

    // -----------------------------------------------------------------

    public function render()
    {
        $errorCount = $this->reviewing ? $this->getErrorCount() : 0;

        return view('admin.menus.import.review', [
            'conditions'  => MenuDiskCondition::orderBy('name')->get(),
            'linkModes'   => self::LINK_MODES,
            'errorCount'  => $errorCount,
        ]);
    }
}
