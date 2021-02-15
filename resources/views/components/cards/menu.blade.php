@isset ($disk)
    <div class="card bg-dark mb-4">
        <div class="card-header text-center">
            <h2 class="text-uppercase"><a href="{{ route('menus.index') }}">Random Menu</a></h2>
        </div>
        <div class="card-body p-0">
            @isset ($disk)
                <figure>
                    <img class="w-100 pixelated" src="{{ asset('storage/images/menu_screenshots/'.$disk->screenshots->first()->file) }}" alt="Screenshot of {{ $disk->menu->menuSet->name }} #{{ $disk->menu->label }}{{ $disk->label }}">
                    <figcaption class="py-2 px-3">
                        <div class="figcaption-caret"><i class="fas fa-angle-up fa-2x"></i></div>
                        <div class="figcaption-title"><a href="{{ route('menus.show', $disk->menu->menuSet) }}">{{ $disk->menu->menuSet->name }} #{{ $disk->menu->label }}{{ $disk->label }}</a></div>
                        @if ($disk->menu->date !== null)
                            <div class="figcaption-note">{{ $disk->menu->date->format('F j, Y') }}</div>
                        @endif
                        @if ($disk->donatedBy !== null)
                            <div class="figcaption-subtitle mb-2">Donated by <strong>{{ $disk->donatedBy->ind_name }}</strong></div>
                        @endif
                    </figcaption>
                </figure>
                <div class="px-0">
                    <ol class="list-unstyled striped border-top border-black">
                        @forelse ($disk->contents->sortBy('order')->take(5) as $content)
                            <li class="p-2">
                                @if ($content->release)
                                    <a href="{{ route('games.show', $content->release->game) }}">{{ $content->release->game->game_name }} {{ $content->version }}</a>
                                    @if (!$content->subtype)
                                        <a href="{{ route('games.releases.show', $content->release) }}" class="text-muted d-inline-block" title="Release information">
                                            <i class="fas fa-info-circle"></i>
                                        </a>
                                    @endif
                                @elseif ($content->game)
                                    <a href="{{ route('games.show', $content->game) }}">{{ $content->game->game_name }} {{ $content->version }}</a>
                                @elseif ($content->menuSoftware)
                                    @if (isset($software) && $software->id === $content->menuSoftware->id)
                                        <b>{{ $content->menuSoftware->name }} {{ $content->version }}</b>
                                    @else
                                        <a href="{{ route('menus.software', $content->menuSoftware) }}" class="d-inline-block">
                                            {{ $content->menuSoftware->name }} {{ $content->version }}
                                        </a>
                                    @endif
                                @endif
                                @if ($content->menuSoftware && $content->menuSoftware->demozoo_id)
                                    <a href="https://demozoo.org/productions/{{ $content->menuSoftware->demozoo_id }}/" class="d-inline-block">
                                        <img src="{{ asset('images/demozoo-16x16.png') }}" class="border-0" alt="Demozoo link for {{ $content->menuSoftware->name }}">
                                    </a>
                                @endif

                                @if ($content->subtype)
                                    <small class="text-muted">[{{ $content->subtype }}]</small>
                                @endif

                                @if ($content->requirements)
                                    <small class="text-muted">({{ $content->requirements }})</small>
                                @endif
                            </li>
                        @empty
                            <span class="text-muted">Unknown content</span>
                        @endforelse
                        @if ($disk->contents->count() > 5)
                            <li class="p-2 text-muted">&hellip; <small>({{$disk->contents->count() - 5}} more)</small></li>
                        @endif
                    </ol>

                    <a class="d-block text-end p-2 pt-0"
                        href="{{ route('menus.show', ['set' => $disk->menu->menuSet, 'page' => $disk->menuset_page_number]) }}#menudisk-{{ $disk->id }}">
                        More <i class="fas fa-chevron-right"></i>
                    </a>

                </div>
            @endisset
        </div>
    </div>
@endisset
