<li>
    @if ($content->release)
        <a href="{{ route('games.show', $content->release->game) }}">{{ $content->release->game->game_name }} {{ $content->version }}</a>
        @if (!$content->subtype)
            @php
                $descriptions = ReleaseDescriptionHelper::menuDescriptions($content->release)
            @endphp
            @if (count($descriptions) > 0)
                <a href="javascript:;" class="text-muted d-inline-block" data-bs-toggle="popover" data-bs-html="true" data-bs-content-selector="#release-info-{{ $content->id }}">
                    <i class="fas fa-info-circle"></i>
                </a>
                <div id="release-info-{{ $content->id }}" class="visually-hidden">
                    @foreach ($descriptions as $description)
                        <p class="mb-1">{!! Helper::bbCode($description) !!}</p>
                    @endforeach
                </div>
            @endif
        @endif
    @elseif ($content->game)
        <a class="d-inline-block" href="{{ route('games.show', $content->game) }}">{{ $content->game->game_name }} {{ $content->version }}</a>
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
            <img src="{{ $demozoo_icon_url ?? asset('images/demozoo-16x16.png') }}" class="demozoo border-0" alt="Demozoo link for {{ $content->menuSoftware->name }}">
        </a>
    @endif

    @if ($content->subtype)
        <small class="text-muted">[{{ $content->subtype }}]</small>
    @endif

    @if ($content->requirements)
        <small class="text-muted">({{ $content->requirements }})</small>
    @endif
</li>
