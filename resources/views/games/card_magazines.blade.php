@if ($game->magazineIndices->isNotEmpty())
    <div class="card bg-dark mb-4">
        <div class="card-header text-center">
            <h2 class="text-uppercase">Magazines</h2>
        </div>
        <div class="card-body p-0 striped">
            @foreach ($game->magazineIndices as $index)
            <div class="p-2">
                <span class="float-end">{{ $index->score }}</span>
                <h3 class="fs-6 mb-0">
                    @contributor
                        <a class="d-inline-block me-1"
                            href="{{ route('admin.magazines.issues.edit', ['magazine' => $index->magazineIssue->magazine, 'issue' => $index->magazineIssue]) }}">
                            <small><i class="fas fa-pencil-alt text-contributor"></i></small>
                        </a>
                    @endcontributor
                    {{ $index->magazineIssue->label }}
                    @if ($index->page)
                        <span class="text-muted ms-2">p{{$index->page}}</span>
                    @endif
                    @if ($index->read_url)
                        <a class="d-inline-block ms-2"
                            href="{{ $index->read_url }}">
                            <i class="fa-solid fa-fw fa-book-open"></i>
                        </a>
                    @endif
                </h3>
            </div>
            @endforeach
        </div>
        <div class="card-footer text-muted text-center">
            <small>Magazines hosted by <a href="https://archive.org">Internet Archive</a>.</small>
        </div>
    </div>
@endif
