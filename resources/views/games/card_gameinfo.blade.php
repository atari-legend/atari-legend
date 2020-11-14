<div class="card bg-dark mb-4 card-game">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Game info</h2>
    </div>
    <div class="card-body p-0 striped">
        @if ($developersLogos->isNotEmpty())
            <div class="row p-2 g-0">
                <div class="col text-center">
                    @foreach ($developersLogos as $logo)
                        <img class="company-logo bg-black" src="{{ asset('storage/images/company_logos/'.$logo) }}" alt="Logo of the developer company">
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
                            <a href="{{ route('games.search', ['developer' => $developer->pub_dev_name]) }}">{{ $developer->pub_dev_name }}</a>
                            @contributor
                                <a class="d-inline-block" href="{{ config('al.legacy.base_url').'/admin/company/company_edit.php?comp_id='.$developer->pub_dev_id }}">
                                    <small><i class="fas fa-pencil-alt text-contributor"></i></small>
                                </a>
                            @endcontributor
                            @if ($developer->texts->isNotEmpty() && $developer->texts->first()->file !== null)
                                <a class="lightbox-link d-inline-block" href="{{ asset('storage/images/company_logos/'.$developer->texts->first()->file) }}">
                                    <i class="far fa-image ml-1"></i>
                                </a>
                            @endif
                            @if ($developer->texts->isNotEmpty() && $developer->texts->first()->pub_dev_profile !== null && trim($developer->texts->first()->pub_dev_profile) !== '')
                                <a href="javascript:;" class="ml-1" data-target="#profile-developer-{{ $developer->pub_dev_id }}" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="profile-developer-{{ $developer->pub_dev_id }}"><i class="fas fa-info-circle text-muted"></i></a>
                                <p class="collapse mt-2 p-2 bg-black text-muted border border-secondary" id="profile-developer-{{ $developer->pub_dev_id }}">
                                    {!! Helper::bbCode($developer->texts->first()->pub_dev_profile) !!}
                                </p>
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
                            <a href="{{ route('games.search', ['individual_id' => $gameIndividual->individual->ind_id]) }}">{{ $gameIndividual->individual->ind_name }}</a>
                            @contributor
                                <a class="d-inline-block" href="{{ config('al.legacy.base_url').'/admin/individuals/individuals_edit.php?ind_id='.$gameIndividual->individual->ind_id }}">
                                    <small><i class="fas fa-pencil-alt text-contributor"></i></small>
                                </a>
                            @endcontributor
                            {{-- We have to use trim() here because the profile column in the database contains 'empty' profiles full of spaces --}}
                            @if ($gameIndividual->individual->text !== null && $gameIndividual->individual->text->ind_profile !== null && trim($gameIndividual->individual->text->ind_profile) !== '')
                                <a href="javascript:;" class="ml-1" data-target="#profile-individual-{{ $loop->index }}-{{ $gameIndividual->individual->ind_id }}" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="profile-individual-{{ $loop->index }}-{{ $gameIndividual->individual->ind_id }}"><i class="fas fa-info-circle text-muted"></i></a>
                            @endif
                            @if ($gameIndividual->individual->text !== null && $gameIndividual->individual->text->file !== null)
                                <a class="lightbox-link d-inline-block" href="{{ asset('storage/images/individual_screenshots/'.$gameIndividual->individual->text->file) }}">
                                    <i class="far fa-image"></i>
                                </a>
                            @endif
                            @if ($gameIndividual->individual->interviews->isNotEmpty())
                                <a class="d-inline-block" href="{{ route('interviews.show', ['interview' => $gameIndividual->individual->interviews->first()]) }}">
                                    <i class="far fa-newspaper"></i>
                                </a>
                            @endif
                            <br>
                            @if ($gameIndividual->role !== null)
                                <small class="text-muted">{{ $gameIndividual->role->name }}</small>
                            @endif
                            @if ($gameIndividual->individual->text !== null && $gameIndividual->individual->text->ind_profile !== null && $gameIndividual->individual->text->ind_profile !== '')
                                <p class="collapse mt-2 p-2 bg-black text-muted border border-secondary" id="profile-individual-{{ $loop->index }}-{{ $gameIndividual->individual->ind_id }}">
                                    {!! Helper::bbCode($gameIndividual->individual->text->ind_profile) !!}
                                </p>
                            @endif
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
                        <a href="{{ route('games.search', ['genre' => $genre->name]) }}">{{ $genre->name }}</a>@if (!$loop->last), @endif
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
                        @if ($game->number_players_on_same_machine !== null && $game->number_players_on_same_machine !== 0)
                            {{ $game->number_players_on_same_machine }} <small class="text-muted">(Same machine)</small><br>
                        @endif
                        @if ($game->number_players_multiple_machines !== null && $game->number_players_multiple_machines !== 0)
                            {{ $game->number_players_multiple_machines }} <small class="text-muted">(Multiple linked machines)</small>
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
                                <a href="http://www.lemonamiga.com/?game_id={{ $vs->amiga_id }}"><img class="w-25" src="{{ asset('images/game/Amiga.png') }}" alt="Amiga logo"></a>
                            @endif
                            @if ($vs->C64_id !== null && $vs->C64_id > 0)
                                <a href="http://www.lemon64.com/?game_id={{ $vs->C64_id }}"><img class="w-25" src="{{ asset('images/game/c64.jpg') }}" alt="Commodore 64 logo"></a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</div>
