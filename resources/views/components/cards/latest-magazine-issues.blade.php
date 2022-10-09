<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Latest magazines</h2>
    </div>
    <div class="card-body p-0 striped">
        @foreach ($issues as $issue)
            <div class="row g-0 p-2 pb-3">
                <h3 class="fs-6">
                    <a class="d-inline-block"
                        href="{{ route('magazines.show', ['magazine' => $issue->magazine, 'page' => $issue->magazine_page_number]) }}#magazine-issue-{{ $issue->id }}">
                        {{ $issue->display_label }}
                    </a>
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
                        <div class="mb-1">
                            <span class="text-muted">Published:</span> {{ $issue->published->format('F Y') }}
                        </div>
                    @endif
                    @if ($issue->magazine?->location !== null)
                        <div class="mb-1">
                            <span class="text-muted">Origin:</span> {{ $issue->magazine->location->name }}
                        </div>
                    @endif

                    @if ($issue->indices->whereNotNull('game_id')->isNotEmpty())
                        <div class="mb-2">
                            <span class="text-muted">Game reviews:</span>
                            @foreach ($issue->indices->whereNotNull('game_id')->take(5)->sortBy('game.game_name') as $index)
                                <a href="{{ route('games.show', $index->game) }}">{{ $index->game->game_name }}</a>@if (!$loop->last),@endif
                            @endforeach
                            @if ($issue->indices->whereNotNull('game_id')->count() > 5)
                                , &hellip;
                            @endif
                        </div>
                    @endif

                    @if ($issue->updated_at)
                        <div class="mb-2">
                            <span class="text-muted">Updated:</span> {{ $issue->updated_at->format("F d, Y") }}
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
        <div class="text-center p-2">
            <a href="{{ route('changelog.index') }}">View all database changes</a>
        </div>
    </div>
</div>
