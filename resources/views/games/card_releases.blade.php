<div class="card bg-dark mb-4 card-game">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Releases</h2>
    </div>
    <div class="striped">
        @forelse ($game->releases as $release)
            <div class="card-body px-0 py-1 text-center">
                @if ($release->date !== null)
                    <h3 class="m-0 text-h6 text-audiowide">{{ $release->date->year }}</h3>
                @else
                    <h3 class="m-0 text-h6 text-audiowide">[no date]</h3>
                @endif
            </div>
            <div class="card-body p-0">
                @if ($release->name !== null && $release->name !== '')
                    <div class="row p-2 g-0">
                        <div class="col-4 text-muted">Name</div>
                        <div class="col-8">{{ $release->name }}</div>
                    </div>
                @endif

                @if ($release->publisher !== null)
                    <div class="row p-2 g-0">
                        <div class="col-4 text-muted">Publisher</div>
                        <div class="col-8">
                            <a href="{{ route('games.search', ['publisher' => $release->publisher->pub_dev_name]) }}">{{ $release->publisher->pub_dev_name }}</a>
                            @if ($release->publisher->texts->isNotEmpty() && $release->publisher->texts->first->file !== null)
                                <i class="far fa-image"></i>
                            @endif
                            @if ($release->publisher->texts->isNotEmpty() && $release->publisher->texts->first()->pub_dev_profile !== null && $release->publisher->texts->first()->pub_dev_profile !== '')
                                <a href="javascript:;" class="ml-1" data-target="#profile-publisher-{{ $release->id }}-{{ $release->publisher->pub_dev_id }}" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="profile-publisher-{{ $release->id }}-{{ $release->publisher->pub_dev_id }}"><i class="fas fa-info-circle text-muted"></i></a>
                                <p class="collapse mt-2 p-2 bg-black text-muted border border-secondary" id="profile-publisher-{{ $release->id }}-{{ $release->publisher->pub_dev_id }}">
                                    {!! Helper::bbCode($release->publisher->texts->first()->pub_dev_profile) !!}
                                </p>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($release->distributors->isNotEmpty())
                    <div class="row p-2 g-0">
                        <div class="col-4 text-muted">
                            {{ Str::plural('Distributor', $release->distributors->count())}}
                        </div>
                        <div class="col-8">
                            @foreach ($release->distributors as $distributor)
                                <div>
                                    {{ $distributor->pub_dev_name }}
                                    @if ($distributor->texts->isNotEmpty() && $distributor->texts->first->file !== null)
                                        <i class="far fa-image"></i>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($release->type !== null)
                    <div class="row p-2 g-0">
                        <div class="col-4 text-muted">Type</div>
                        <div class="col-8">
                            <div>{{ $release->type }}</div>
                        </div>
                    </div>
                @endif

                @if ($release->status !== null)
                    <div class="row p-2 g-0">
                        <div class="col-4 text-muted">Status</div>
                        <div class="col-8">
                            <div>{{ $release->status }}</div>
                        </div>
                    </div>
                @endif

                @if ($release->license !== null)
                    <div class="row p-2 g-0">
                        <div class="col-4 text-muted">License</div>
                        <div class="col-8">
                            <div>{{ $release->license }}</div>
                        </div>
                    </div>
                @endif

                @if ($release->hd_installable === 1)
                    <div class="row p-2 g-0">
                        <div class="col-4 text-muted">HD Ready</div>
                        <div class="col-8">
                            <div>Yes</div>
                        </div>
                    </div>
                @endif

                @if ($release->locations->isNotEmpty())
                    <div class="row p-2 g-0">
                        <div class="col-4 text-muted">
                            {{ Str::plural('Location', $release->locations->count())}}
                        </div>
                        <div class="col-8">
                            @foreach ($release->locations as $location)
                                <div class="mb-1">
                                    @if ($location->country_iso2 !== null)
                                        <span class="flag-icon flag-icon-{{ strtolower($location->country_iso2) }} mr-1"></span>
                                    @endif
                                    {{ $location->name }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($release->languages->isNotEmpty())
                    <div class="row p-2 g-0">
                        <div class="col-4 text-muted">
                            Languages
                        </div>
                        <div class="col-8">
                            @foreach ($release->languages as $language)
                                {{ $language->name }}@if (!$loop->last), @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($release->resolutions->isNotEmpty())
                    <div class="row p-2 g-0">
                        <div class="col-4 text-muted">
                            {{ Str::plural('Resolution', $release->resolutions->count())}}
                        </div>
                        <div class="col-8">
                            @foreach ($release->resolutions as $resolution)
                                <div>
                                    {{ $resolution->name }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($release->memoryEnhanced->isNotEmpty() || $release->systemEnhanced->isNotEmpty())
                    <div class="row p-2 g-0">
                        <div class="col-4 text-muted">
                            Enhanced
                        </div>
                        <div class="col-8">
                            @foreach ($release->memoryEnhanced as $enhanced)
                                <div>
                                    {{ $enhanced->memory->memory ?? ''}}
                                    @if ($enhanced->enhancement !== null)
                                        <small class="text-muted">{{ $enhanced->enhancement->name}}</small>
                                    @endif
                                </div>
                            @endforeach
                            @foreach ($release->systemEnhanced as $enhanced)
                                <div>
                                    {{ $enhanced->system->name ?? ''}}
                                    @if ($enhanced->enhancement !== null)
                                        <small class="text-muted">{{ $enhanced->enhancement->name}}</small>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($release->memoryMinimums->isNotEmpty() || $release->memoryIncompatibles->isNotEmpty())
                    <div class="row p-2 g-0">
                        <div class="col-4 text-muted">
                            Memory
                        </div>
                        <div class="col-8">
                            @if ($release->memoryMinimums->isNotEmpty())
                                <div>
                                    <small class="text-muted">Minimum: </small>
                                    @foreach ($release->memoryMinimums as $memory)
                                        {{ $memory->memory }}@if (!$loop->last), @endif
                                    @endforeach
                                </div>
                            @endif
                            @if ($release->memoryIncompatibles->isNotEmpty())
                                <div>
                                    <small class="text-muted">Incompatible: </small>
                                    @foreach ($release->memoryIncompatibles as $memory)
                                        {{ $memory->memory }}@if (!$loop->last), @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($release->systemIncompatibles->isNotEmpty() || $release->emulatorIncompatibles->isNotEmpty() || $release->tosIncompatibles->isNotEmpty())
                    <div class="row p-2 g-0">
                        <div class="col-4 text-muted">
                            Incompatible
                        </div>
                        <div class="col-8">
                            @if ($release->systemIncompatibles->isNotEmpty())
                                <div>
                                    <small class="text-muted">Systems: </small>
                                    @foreach ($release->systemIncompatibles as $system)
                                        {{ $system->name }}@if (!$loop->last), @endif
                                    @endforeach
                                </div>
                            @endif
                            @if ($release->emulatorIncompatibles->isNotEmpty())
                                <div>
                                    <small class="text-muted">Emulators: </small>
                                    @foreach ($release->emulatorIncompatibles as $emulator)
                                        {{ $emulator->name }}@if (!$loop->last), @endif
                                    @endforeach
                                </div>
                            @endif
                            @if ($release->tosIncompatibles->isNotEmpty())
                                <div>
                                    <small class="text-muted">TOS: </small>
                                    @foreach ($release->tosIncompatibles as $incompatible)
                                        {{ $incompatible->tos->name }}
                                        @if ($incompatible->language !== null) {{ $incompatible->language->id }} @endif
                                        @if (!$loop->last), @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($release->akas->isNotEmpty())
                    <div class="row p-2 g-0">
                        <div class="col-4 text-muted">
                            AKA
                        </div>
                        <div class="col-8">
                            @foreach ($release->akas as $aka)
                                <div>
                                    @if ($aka->language !== null)
                                        <small class="text-muted">{{ $aka->language->name }}: </small>
                                    @endif
                                    {{ $aka->name }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($release->trainers->isNotEmpty())
                    <div class="row p-2 g-0">
                        <div class="col-4 text-muted">
                            Trainer
                        </div>
                        <div class="col-8">
                            @foreach ($release->trainers as $trainer)
                                {{ $trainer->name }}@if (!$loop->last), @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($release->copyProtections->isNotEmpty())
                    <div class="row p-2 g-0">
                        <div class="col-4 text-muted">
                            Copy protection
                        </div>
                        <div class="col-8">
                            @foreach ($release->copyProtections as $protection)
                                <div>
                                    {{ $protection->name }}
                                    @if ($protection->pivot->notes !== null)
                                        <small class="text-muted">{{$protection->pivot->notes}}</small>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($release->diskProtections->isNotEmpty())
                    <div class="row p-2 g-0">
                        <div class="col-4 text-muted">
                            Disk protection
                        </div>
                        <div class="col-8">
                            @foreach ($release->diskProtections as $protection)
                                <div>
                                    {{ $protection->name }}
                                    @if ($protection->pivot->notes !== null)
                                        <small class="text-muted">{{$protection->pivot->notes}}</small>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>
        @empty
            <div class="card-body p-2 text-center">
                <p class="card-text">No release for this game</p>
            </div>
        @endforelse
    </div>
</div>
