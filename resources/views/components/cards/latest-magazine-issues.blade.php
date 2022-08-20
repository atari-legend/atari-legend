<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Latest magazines</h2>
    </div>
    <div class="card-body p-0 striped">
        @foreach ($issues as $issue)
            <div class="row g-0 p-2 pb-3">
                <h3 class="fs-6">
                    {{ $issue->magazine->name }} {{ $issue->issue }}
                    @if ($issue->read_url)
                        <a class="d-inline-block ms-2" href="{{ $issue->read_url }}">
                            <i class="fa-solid fa-fw fa-book-open"></i>
                        </a>
                    @endif
                </h3>
                <div class="col-3">
                    <img src="{{ $issue->image }}" class="img-fluid bg-black" alt="Cover for {{ $issue->label }}">
                </div>
                <div class="col ps-2">
                    @if ($issue->published)
                        <span class="text-muted">Published:</span> {{ $issue->published->format('F Y') }}<br>
                    @endif
                    @if ($issue->magazine?->location !== null)
                        <span class="text-muted">Origin:</span> {{ $issue->magazine->location->name }}<br>
                    @endif

                    @if ($issue->indices->whereNotNull('game_id')->isNotEmpty())
                        <span class="text-muted ">Game reviews:</span>
                        @foreach ($issue->indices->whereNotNull('game_id')->take(5)->sortBy('game.game_name') as $index)
                            <a href="{{ route('games.show', $index->game) }}">{{ $index->game->game_name }}</a>@if (!$loop->last),@endif
                        @endforeach
                        @if ($issue->indices->whereNotNull('game_id')->count() > 5)
                            , &hellip;
                        @endif
                        <br>
                    @endif

                    @if ($issue->updated_at)
                        <span class="text-muted">Updated:</span> {{ $issue->updated_at->format("F d, Y") }}
                    @endif
                </div>
            </div>
        @endforeach
        <div class="text-center p-2">
            <a href="{{ route('changelog.index') }}">View all database changes</a>
        </div>
    </div>
</div>
