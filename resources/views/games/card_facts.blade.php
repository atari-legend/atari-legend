@if ($game->facts->isNotEmpty())
    <div class="card bg-dark mb-4">
        <div class="card-header text-center">
            <h2 class="text-uppercase">Facts</h2>
        </div>
        <div class="card-body p-0 striped lightbox-gallery">
            @foreach ($game->facts as $fact)
                <div class="p-2">
                    <p class="card-text">{!! Helper::bbCode(nl2br($fact->game_fact)) !!}</p>
                    @foreach($fact->screenshots as $screenshot)
                        <a class="lightbox-link" href="{{ $screenshot->getUrl('game_fact') }}">
                            <img class="w-100 mb-2" src="{{ $screenshot->getUrl('game_fact') }}" alt="Game fact picture">
                        </a>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
@endif
