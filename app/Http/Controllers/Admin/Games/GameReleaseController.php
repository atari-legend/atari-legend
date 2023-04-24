<?php

namespace App\Http\Controllers\Admin\Games;

use App\Helpers\ChangelogHelper;
use App\Helpers\ReleaseHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Crew;
use App\Models\Game;
use App\Models\Language;
use App\Models\Location;
use App\Models\PublisherDeveloper;
use App\Models\Release;
use App\View\Components\Admin\Crumb;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GameReleaseController extends Controller
{
    public function index(Game $game)
    {
        return view('admin.games.games.releases.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.games.games.index'), 'Games'),
                    new Crumb(route('admin.games.games.edit', $game), $game->game_name),
                    new Crumb(route('admin.games.releases.index', $game), 'Releases'),
                ],
                'game'        => $game,
            ]);
    }

    public function show(Game $game, Release $release)
    {
        $referenceData = $this->getReferenceData();

        return view('admin.games.games.releases.edit')
            ->with(
                array_merge(
                    $referenceData,
                    [
                        'breadcrumbs' => [
                            new Crumb(route('admin.games.games.index'), 'Games'),
                            new Crumb(route('admin.games.games.edit', $release->game), $release->game->game_name),
                            new Crumb(route('admin.games.releases.index', $release->game), 'Releases'),
                            new Crumb(
                                route('admin.games.releases.show', ['game' => $release->game, 'release' => $release]),
                                $release->full_label,
                                ReleaseHelper::siblingReleasesCrumbs($release)
                            ),
                        ],
                        'game'       => $release->game,
                        'release'    => $release,
                    ]
                )
            );
    }

    public function create(Game $game)
    {
        $referenceData = $this->getReferenceData();

        return view('admin.games.games.releases.edit')
            ->with(
                array_merge(
                    $referenceData,
                    [
                        'breadcrumbs' => [
                            new Crumb(route('admin.games.games.index'), 'Games'),
                            new Crumb(route('admin.games.games.edit', $game), $game->game_name),
                            new Crumb(route('admin.games.releases.index', $game), 'Releases'),
                            new Crumb(route('admin.games.releases.create', $game), 'Add new release'),
                        ],
                        'game'       => $game,
                    ]
                )
            );
    }

    public function store(Game $game, Request $request)
    {
        $this->validateRelease($request);
        $release = Release::create([
            'game_id' => $game->getKey(),
        ]);
        $this->updateRelease($release, $request);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Game Release',
            'section_id'       => $release->getKey(),
            'section_name'     => $release->game->game_name,
            'sub_section'      => 'Release Info',
            'sub_section_id'   => $release->getKey(),
            'sub_section_name' => $release->full_label ?? $release->game->game_name,
        ]);

        return redirect()->route('admin.games.releases.show', [
            'game'    => $release->game,
            'release' => $release,
        ]);
    }

    public function update(Game $game, Release $release, Request $request)
    {
        $this->validateRelease($request);
        $this->updateRelease($release, $request);

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Game Release',
            'section_id'       => $release->getKey(),
            'section_name'     => $release->game->game_name,
            'sub_section'      => 'Release Info',
            'sub_section_id'   => $release->getKey(),
            'sub_section_name' => $release->full_label ?? $release->game->game_name,
        ]);

        return redirect()->route('admin.games.releases.index', $release->game);
    }

    private function validateRelease(Request $request)
    {
        $request->validate([
            'year'         => 'nullable|numeric|between:1984,' . date('Y'),
            'publisher'    => 'numeric',
            'type'         => ['nullable', Rule::in(Release::TYPES)],
            'license'      => ['nullable', Rule::in(Release::LICENSES)],
            'status'       => ['nullable', Rule::in(Release::STATUSES)],
            'locations'    => 'nullable|array',
            'crews'        => 'nullable|array',
            'languages'    => 'nullable|array',
            'distributors' => 'nullable|array',
        ]);
    }

    private function updateRelease(Release $release, Request $request)
    {
        $release->update([
            'name'    => $request->name,
            'date'    => $request->year ? Carbon::createFromDate($request->year, 1, 1) : null,
            'type'    => $request->type,
            'license' => $request->license,
            'status'  => $request->status,
            'notes'   => $request->notes,
        ]);

        if ($release->publisher?->getKey() !== $request->publisher) {
            $release->publisher()->associate(PublisherDeveloper::findOrFail($request->publisher));
            $release->save();
        }

        if ($release->locations->pluck('id') !== $request->locations) {
            $release->locations()->detach();
            if ($request->locations) {
                $release->locations()->saveMany(
                    collect($request->locations)
                        ->map(fn ($id) => Location::findOrFail($id))
                        ->all()
                );
                $release->save();
            }
        }

        if ($release->crews->pluck('id') !== $request->crews) {
            $release->crews()->detach();
            if ($request->crews) {
                $release->crews()->saveMany(
                    collect($request->crews)
                        ->map(fn ($id) => Crew::findOrFail($id))
                        ->all()
                );
                $release->save();
            }
        }

        if ($release->languages->pluck('id') !== $request->languages) {
            $release->languages()->detach();
            if ($request->languages) {
                $release->languages()->saveMany(
                    collect($request->languages)
                        ->map(fn ($id) => Language::findOrFail($id))
                        ->all()
                );
                $release->save();
            }
        }

        if ($release->distributors->pluck('id') !== $request->distributors) {
            $release->distributors()->detach();
            if ($request->distributors) {
                $release->distributors()->saveMany(
                    collect($request->distributors)
                        ->map(fn ($id) => PublisherDeveloper::findOrFail($id))
                        ->all()
                );
                $release->save();
            }
        }
    }

    private function getReferenceData(): array
    {
        $companies = PublisherDeveloper::orderBy('pub_dev_name')->get();
        $licenses = Release::LICENSES;
        $types = Release::TYPES;
        $statuses = Release::STATUSES;
        $locations = Location::orderBy('continent_code', 'asc')
            ->orderBy('type', 'asc')
            ->orderBy('name', 'asc')
            ->get();
        $crews = Crew::orderBy('crew_name')
            ->get();
        $languages = Language::orderBy('name')
            ->get();

        return [
            'companies'  => $companies,
            'licenses'   => $licenses,
            'types'      => $types,
            'statuses'   => $statuses,
            'locations'  => $locations,
            'crews'      => $crews,
            'languages'  => $languages,
        ];
    }
}
