<div class="card bg-dark mb-4 card-links">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Links</h2>
    </div>

    <div class="card-header p-2">
        <ul class="list-unstyled">
            <li class="w-45 d-inline-block">
                <a href="{{ route('links.index') }}" class="{{ isset($category) ? '' : 'fw-bold text-white' }}">All</a>
            </li>
            @foreach ($categories as $c)
                <li class="w-45 d-inline-block">
                    @if ($c->links->count() > 0)
                        <a href="{{ route('links.index', ['category' => $c]) }}"
                            class="{{ (isset($category) && $category->getKey() === $c->getKey()) ? 'fw-bold text-primary' : '' }}">{{ $c->name }}</a>
                    @else
                        <span class="text-muted">{{ $c->name }}</span>
                    @endif
                    <small class="text-muted">({{ $c->links->count() }})</small>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="card-body p-0 striped lightbox-gallery">
        @foreach ($links as $link)
            <div class="row g-0 p-2 pb-4 pt-4 pt-md-3 pb-md-3">
                <div class="col-md-4">
                    @if ($link->file)
                        <a class="lightbox-link" href="{{ asset('storage/'. $link->path) }}">
                            <img class="w-100 cropped mb-2 mb-md-0" src="{{ route('links.screenshot', $link) }}" alt="Screenshot of link {{ $link->name }}" loading="lazy">
                        </a>
                    @endif
                </div>
                <div class="col-md-8 ps-2">
                    @if ($link->inactive === 1)
                        <small class="text-warning mt-1 float-end"><i class="fas fa-exclamation-triangle"></i> Appears to be inactive</small>
                    @endif

                    <h3 class="card-title fs-5 text-audiowide">
                        <a href="{{ $link->url }}">{{ $link->name }}</a>
                        @contributor
                            <a href="{{ route('admin.links.links.edit', $link) }}">
                                <small><i class="fas fa-pencil-alt text-contributor"></i></small>
                            </a>
                        @endcontributor
                    </h3>
                    <p class="card-subtitle text-muted">Added on {{ date('F j, Y', $link->date) }} by {{ Helper::user($link->user) }}</p>
                    <div class="mb-2"><small><a href="{{ $link->url }}">{{ $link->url }}</a></small></div>
                    <p class="card-text">{{ $link->description }}</p>
                </div>
            </div>
        @endforeach

        {{ $links->withQueryString()->links() }}
    </div>
</div>
