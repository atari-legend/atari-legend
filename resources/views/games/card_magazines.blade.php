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
                        @if ($index->magazineIssue?->magazine?->location?->country_iso2 !== null)
                            <span title="{{ $index->magazineIssue->magazine->location->name }}"
                                class="fi fi-{{ strtolower($index->magazineIssue->magazine->location->country_iso2) }} me-1"></span>
                        @endif
                        <a class="d-inline-block"
                            href="{{ route('magazines.show', ['magazine' => $index->magazineIssue->magazine, 'page' => $index->magazineIssue->magazine_page_number]) }}#magazine-issue-{{ $index->magazineIssue->id }}">
                            {{ $index->magazineIssue->label }}
                        </a>
                        @contributor
                            <a class="d-inline-block ms-1"
                                href="{{ route('admin.magazines.issues.edit', ['magazine' => $index->magazineIssue->magazine, 'issue' => $index->magazineIssue]) }}">
                                <small><i class="fas fa-pencil-alt text-contributor"></i></small>
                            </a>
                        @endcontributor
                        @if ($index->page)
                            <span class="text-muted ms-2">p{{ $index->page }}</span>
                        @endif
                        @if ($index->magazineIssue->read_url)
                            <a class="d-inline-block ms-2" href="{{ $index->magazineIssue->read_url }}">
                                <i class="fa-solid fa-fw fa-book-open"></i>
                            </a>
                        @endif
                    </h3>
                </div>
            @endforeach
        </div>
    </div>
@endif
