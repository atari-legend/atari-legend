@if ($game->releases->isNotEmpty())
    <div class="card bg-dark mb-4 card-game">
        <div class="card-header text-center">
            <h2 class="text-uppercase">Releases</h2>
        </div>
        <div class="striped">
            @foreach ($game->releases as $release)
                <div class="card-body pl-2 py-2 ">
                    <div class="m-0">
                        @if (isset($currentRelease) && $currentRelease->id === $release->id)
                            <i class="fas fa-chevron-right"></i> {{ $release->year }}
                        @else
                            <a href="{{ route('games.releases.show', ['release' => $release]) }}">{{ $release->year }}</a>
                        @endif
                        @contributor
                            <a class="d-inline-block ml-1" href="{{ URL::to('/legacy/admin/games/games_release_detail.php?release_id='.$release->id.'&game_id='.$release->game->game_id) }}">
                                <small><i class="fas fa-pencil-alt text-contributor"></i></small>
                            </a>
                        @endcontributor
                        @if ($release->name !== null)
                            <span class="ml-2">{{ $release->name }}</span>
                        @elseif ($release->publisher !== null)
                            <span class="ml-2 text-muted"><span class="text-muted">by</span> {{ $release->publisher->pub_dev_name }}</span>
                        @endif

                        @foreach ($release->locations as $location)
                            @if ($location->country_iso2 !== null)
                                <span title="{{ $location->name }}" class="flag-icon flag-icon-{{ strtolower($location->country_iso2) }} mx-1"></span>
                            @endif
                        @endforeach

                        @if ($release->dumps->isNotEmpty())
                            <span class="ml-2">
                                <i title="Donwloads" class="far fa-save"></i> &times; {{ $release->dumps->count() }}
                            </span>
                        @endif
                    </div>
                </div>

            @endforeach
        </div>
    </div>
@endif
