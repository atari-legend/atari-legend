<div class="card bg-dark mb-4 card-game">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Game info</h2>
    </div>
    <div class="card-body p-0 striped">
        @if ($developersLogos->isNotEmpty())
            <div class="row p-2 g-0">
                <div class="col text-center">
                    @foreach ($developersLogos as $logo)
                        <img class="company-logo bg-black" src="{{ asset('storage/images/company_logos/'.$logo) }}">
                    @endforeach
                </div>
            </div>
        @endif
        @if ($game->developers->isNotEmpty())
            <div class="row p-2 g-0">
                <div class="col-4 text-muted">
                    {{ Str::plural('Developer', $game->developers->count())}}
                </div>
                <div class="col-8">
                    @foreach ($game->developers as $developer)
                        <div>
                            {{ $developer->pub_dev_name }}
                            @if ($developer->texts->isNotEmpty() && $developer->texts->first->file !== null)
                                <i class="far fa-image"></i>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($game->individuals->isNotEmpty())
            <div class="row p-2 g-0">
                <div class="col-4 text-muted">
                    {{ Str::plural('Author', $game->individuals->count())}}
                </div>
                <div class="col-8">
                    @foreach ($game->individuals as $gameIndividual)
                        <div class="mb-1">
                            {{ $gameIndividual->individual->ind_name }}<br>
                            <small class="text-muted">{{ $gameIndividual->role->name }}</small>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($game->programmingLanguages->isNotEmpty())
            <div class="row p-2 g-0">
                <div class="col-4 text-muted">
                    Code
                </div>
                <div class="col-8">
                    @foreach ($game->programmingLanguages as $language)
                        <div class="mb-1">
                            {{ $language->name }}
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($game->akas->isNotEmpty())
            <div class="row p-2 g-0">
                <div class="col-4 text-muted">
                    AKA
                </div>
                <div class="col-8">
                    @foreach ($game->akas as $aka)
                        <div>
                            @if ($aka->language !== null)
                                <small class="text-muted">{{ $aka->language->name }}: </small>
                            @endif
                            {{ $aka->aka_name }}
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($game->engines->isNotEmpty())
            <div class="row p-2 g-0">
                <div class="col-4 text-muted">
                    Engine
                </div>
                <div class="col-8">
                    @foreach ($game->engines as $engine)
                        <div>
                            {{ $engine->name }}
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($game->genres->isNotEmpty())
            <div class="row p-2 g-0">
                <div class="col-4 text-muted">
                    {{ Str::plural('Genre', $game->genres->count()) }}
                </div>
                <div class="col-8">
                    @foreach ($game->genres as $genre)
                        {{ $genre->name }}@if (!$loop->last), @endif
                    @endforeach
                </div>
            </div>
        @endif

        @if ($game->port !== null)
            <div class="row p-2 g-0">
                <div class="col-4 text-muted">
                    Conversion
                </div>
                <div class="col-8">
                    <div class="mb-1">
                        {{ $game->port->name }}
                    </div>
                </div>
            </div>
        @endif

        @if ($game->progressSystem !== null)
            <div class="row p-2 g-0">
                <div class="col-4 text-muted">
                    Progress
                </div>
                <div class="col-8">
                    <div class="mb-1">
                        {{ $game->progressSystem->name }}
                    </div>
                </div>
            </div>
        @endif

        @if ($game->soundHardwares->isNotEmpty())
            <div class="row p-2 g-0">
                <div class="col-4 text-muted">
                    Sound HW
                </div>
                <div class="col-8">
                    @foreach ($game->soundHardwares as $hardware)
                        <div class="mb-1">
                            {{ $hardware->name }}
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($game->controls->isNotEmpty())
            <div class="row p-2 g-0">
                <div class="col-4 text-muted">
                    Controls
                </div>
                <div class="col-8">
                    @foreach ($game->controls as $control)
                        <div class="mb-1">
                            {{ $control->name }}
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($game->number_players_on_same_machine !== null || $game->number_players_multiple_machines !== null)
            <div class="row p-2 g-0">
                <div class="col-4 text-muted">
                    Players
                </div>
                <div class="col-8">
                    <div class="mb-1">
                        @if ($game->number_players_on_same_machine !== null)
                            {{ $game->number_players_on_same_machine }} <small class="text-muted">(Same machine)</small><br>
                        @endif
                        @if ($game->number_players_multiple_machines !== null)
                            {{ $game->number_players_multiple_machines }} <small class="text-muted">(Multiple machine)</small>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if ($game->multiplayer_type !== null || $game->multiplayer_hardware !== null)
            <div class="row p-2 g-0">
                <div class="col-4 text-muted">
                    Multiplayer
                </div>
                <div class="col-8">
                    <div class="mb-1">
                        @if ($game->multiplayer_type !== null)
                            {{ $game->multiplayer_type }}
                        @endif
                        @if ($game->multiplayer_hardware !== null)
                            <small class="text-muted">({{ $game->multiplayer_hardware }})</small>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if ($game->vs->isNotEmpty())
            <div class="row p-2 g-0">
                <div class="col-4 text-muted">
                    Compare
                </div>
                <div class="col-8">
                    @foreach ($game->vs as $vs)
                        <div class="mb-1">
                            @if ($vs->amiga_id !== null && $vs->amiga_id > 0)
                                <a href="http://www.lemonamiga.com/?game_id={{ $vs->amiga_id }}"><img class="w-25" src="{{ asset('images/game/Amiga.png') }}"></a>
                            @endif
                            @if ($vs->C64_id !== null && $vs->C64_id > 0)
                                <a href="http://www.lemon64.com/?game_id={{ $vs->C64_id }}"><img class="w-25" src="{{ asset('images/game/c64.jpg') }}"></a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</div>
