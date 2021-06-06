@if ($similar)
    <div class="card bg-dark mb-4">
        <div class="card-header text-center">
            <h2 class="text-uppercase">Similar Game</h2>
        </div>
        <div class="card-body p-0">
            <figure>
                <img class="w-100 pixelated" src="{{ $similar->screenshots[0]->getUrl('game') }}" alt="Screenshot of {{ $similar->game_name }}">
                <figcaption class="py-2 px-3">
                    <div class="figcaption-caret"><i class="fas fa-angle-up fa-2x"></i></div>
                    <div class="figcaption-title"><a href="{{ route('games.show', ['game' => $similar]) }}">{{ $similar->game_name }}</a></div>
                    @if ($similar->releases->isNotEmpty())
                        <div class="figcaption-note">
                            <a href="{{ route('games.search', ['year' => $similar->releases->first()->year]) }}">{{ $similar->releases->first()->year }}</a>
                        </div>
                    @endif
                    @if ($similar->developers->isNotEmpty())
                        <div class="figcaption-subtitle mb-2"><strong>{{ $similar->developers->first()->pub_dev_name }}</strong></div>
                    @endif
                </figcaption>
            </figure>
        </div>
    </div>
@endif
