<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">
            {{ $magazine->name }}
            @contributor
                <a class="d-inline-block ms-1" href="{{ route('admin.magazines.magazines.edit', $magazine) }}">
                    <small><i class="fas fa-pencil-alt text-contributor"></i></small>
                </a>
            @endcontributor
        </h2>
    </div>
    <div class="card-body p-2">
        <p class="card-text">
            @if ($magazine->location)
                {{ $magazine->name }} originated from <strong>{{ $magazine->location->name }}</strong>.
            @endif
            There are
            {{ $magazine->issues->count() }}
            {{ Str::plural('issue', $magazine->issues->count()) }} of
            {{ $magazine->name }} in the database.
        </p>
    </div>
</div>

@foreach ($issues->sortBy(['published', 'issue', 'label']) as $issue)
    <div class="card bg-dark mb-4 border-top-0" id="magazine-issue-{{ $issue->id }}">
        <div class="card-header">
            @if ($issue->published)
                <span class="text-muted float-end">{{ $issue->published?->format('M Y') }}</span>
            @endif
            <h3 class="fs-5 mb-0">
                {{ $magazine->name }}
                @if ($issue->issue)
                    #{{ $issue->issue }}
                @endif
                @if ($issue->label)
                    {{ $issue->label }}
                @endif
                @if ($issue->read_url)
                    <a class="d-inline-block ms-2" href="{{ $issue->read_url }}">
                        <i class="fa-solid fa-fw fa-book-open"></i>
                    </a>
                @endif
                @contributor
                    <a class="d-inline-block ms-1"
                        href="{{ route('admin.magazines.issues.edit', ['magazine' => $magazine, 'issue' => $issue]) }}">
                        <small><i class="fas fa-pencil-alt text-contributor"></i></small>
                    </a>
                @endcontributor

                <a href="{{ route('magazines.show', ['magazine' => $issue->magazine, 'page' => Request::input('page')]) }}#magazine-issue-{{ $issue->id }}"
                    class="ms-1 issue-link">
                    <i class="fas fa-link text-muted fs-6"></i>
                </a>
            </h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-sm-3 order-sm-last">
                    <img src="{{ $issue->image }}" class="img-fluid bg-black"
                        alt="Cover for {{ $issue->display_label_with_date }}">
                </div>
                <div class="col pt-3 pt-sm-0">
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
                                @foreach ($issue->indices->sortBy(['page', 'game.game_name']) as $index)
                                    <tr class="border-bottom border-secondary">
                                        <td>
                                            @if ($index->game || $index->menuSoftware || $index->individual)
                                                @if ($index->game)
                                                    <a
                                                        href="{{ route('games.show', $index->game) }}">{{ $index->game->game_name }}</a>
                                                @elseif ($index->menuSoftware)
                                                    <a
                                                        href="{{ route('menus.software', $index->menuSoftware) }}">{{ $index->menuSoftware->name }}</a>
                                                @elseif ($index->individual)
                                                    <a
                                                        href="{{ route('games.search', ['individual_id' => $index->individual->ind_id]) }}">{{ $index->individual->ind_name }}</a>
                                                @endif
                                                @if ($index->title)
                                                    : {{ $index->title }}
                                                @endif
                                                @if ($index->score)
                                                    <small class="text-muted ms-2">{{ $index->score }}</small>
                                                @endif
                                            @else
                                                {{ $index->title ?? '-' }}
                                            @endif
                                        </td>
                                        <td class="text-muted">{{ $index->magazineIndexType?->name ?? '-' }}</td>
                                        <td class="text-end">
                                            {{ $index->page }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <span class="text-muted">No index for this magazine</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endforeach

{{ $issues->links() }}
