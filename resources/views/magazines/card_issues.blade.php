<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">{{ $magazine->name }}</h2>
    </div>
    <div class="card-body p-2">
        <p class="card-text">
            There are {{ $magazine->issues->count() }}
            {{ Str::plural('issue', $magazine->issues->count()) }} of
            {{ $magazine->name }}.
        </p>
    </div>
</div>

@foreach ($magazine->issues->sortBy('issue') as $issue)
    <div class="card bg-dark mb-4 border-top-0">
        <div class="card-header">
            @if ($issue->published)
                <span class="text-muted float-end">{{ $issue->published?->format('M Y') }}</span>
            @endif
            <h3 class="fs-5 mb-0">
                {{ $magazine->name }}
                @if ($issue->issue)
                    #{{ $issue->issue }}
                @endif
                @if ($issue->archiveorg_url)
                    <a class="d-inline-block ms-2" href="{{ $issue->archiveorg_url }}">
                        <i class="fa-solid fa-fw fa-book-open"></i>
                    </a>
                @endif
            </h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col">
                    @if ($issue->indices->isNotEmpty())
                        <table class="table table-borderless table-sm">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th class="text-end">Page</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($issue->indices->sortBy('page') as $index)
                                    <tr class="border-bottom border-secondary">
                                        <td>
                                            {{ $index->title }}
                                            @if (!$index->title)
                                                @if ($index->game)
                                                    <a
                                                        href="{{ route('games.show', $index->game) }}">{{ $index->game->game_name }}</a>
                                                @elseif ($index->menuSoftware)
                                                    <a
                                                        href="{{ route('menus.software', $index->menuSoftware) }}">{{ $index->menuSoftware->name }}</a>
                                                @else
                                                    ?
                                                @endif
                                            @endif
                                        </td>
                                        <td class="text-muted">{{ $index->magazineIndexType?->name ?? '-' }}</td>
                                        <td class="text-end">
                                            {{ $index->page }}
                                            @if ($index->page && $index->read_url)
                                                <a class="d-inline-block ms-2" href="{{ $index->read_url }}">
                                                    <i class="fa-solid fa-fw fa-book-open"></i>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <span class="text-muted">No index for this magazine</span>
                    @endif
                </div>
                <div class="col-sm-3">
                    <img src="{{ $issue->image }}" class="img-fluid bg-black"
                        alt="Cover for issue {{ $issue->issue }} of {{ $issue->label }}">
                </div>
            </div>
        </div>
    </div>
@endforeach
