@if ($game->magazineIndices !== null)
    <div class="card bg-dark mb-4">
        <div class="card-header text-center">
            <h2 class="text-uppercase">Magazines</h2>
        </div>
        <div class="card-body p-0 striped">
            @foreach ($game->magazineIndices as $index)
            <div class="p-2">
                <span class="float-end">{{ $index->score }}</span>
                <h3 class="fs-6 mb-0">
                    @if ($index->magazineIssue->archiveorg_url)
                        <a href="{{ $index->magazineIssue->archiveorg_url }}{{ $index->page ? 'page/n'.$index->page : '' }}">{{ $index->magazineIssue->label }}</a>
                    @else
                        {{ $index->magazineIssue->label }}
                    @endif
                    @if ($index->page)
                        <span class="text-muted ms-2">p{{$index->page}}</span>
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
