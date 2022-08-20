@if ($game->non_menu_releases->isNotEmpty())
    <div class="card bg-dark mb-4 card-game">
        <div class="card-header text-center">
            <h2 class="text-uppercase">
                Releases
                <a href="javascript:;" data-bs-content="Official releases and unofficial single cracks. Menus are not included."
                    data-bs-placement="top"
                    data-bs-toggle="popover">
                    <i class="fas fa-info-circle text-muted fs-6"></i>
                </a>
            </h2>

        </div>
        <div class="striped">
            @foreach ($game->non_menu_releases->sortBy('date') as $release)
                <div class="card-body ps-2 py-2 ">
                    <div class="m-0">
                        @if (isset($currentRelease) && $currentRelease->id === $release->id)
                            <i class="fas fa-chevron-right"></i> {{ $release->year }}
                        @else
                            <a href="{{ route('games.releases.show', ['release' => $release]) }}">{{ $release->year }}</a>
                        @endif
                        @contributor
                            <a class="d-inline-block ms-1" href="{{ config('al.legacy.base_url').'/admin/games/games_release_detail.php?release_id='.$release->id.'&game_id='.$release->game->game_id }}">
                                <small><i class="fas fa-pencil-alt text-contributor"></i></small>
                            </a>
                        @endcontributor
                        @if ($release->name !== null && trim($release->name) !== '')
                            <span class="ms-2">{{ $release->name }}</span>
                        @elseif ($release->publisher !== null)
                            <span class="ms-2 text-muted"><span class="text-muted">by</span> {{ $release->publisher->pub_dev_name }}</span>
                        @endif

                        @if ($release->type === 'Unofficial' && $release->license !== 'Non-Commercial')
                            <span class="text-muted"><i title="Unofficial release" class="fas fa-skull-crossbones"></i></span>
                        @endif

                        @foreach ($release->locations as $location)
                            @if ($location->country_iso2 !== null)
                                <span title="{{ $location->name }}" class="fi fi-{{ strtolower($location->country_iso2) }} mx-1"></span>
                            @endif
                        @endforeach

                        @if ($release->dumps->isNotEmpty())
                            <span class="ms-2">
                                <i title="Donwloads" class="far fa-save"></i> &times; {{ $release->dumps->count() }}
                            </span>
                        @endif
                    </div>
                </div>

            @endforeach
        </div>
    </div>
@endif
