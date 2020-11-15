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
                    @if ($c->websites->count() > 0)
                        <a href="{{ route('links.index', ['category' => $c]) }}"
                            class="{{ (isset($category) && $category->website_category_id === $c->website_category_id) ? 'fw-bold text-primary' : '' }}">{{ $c->website_category_name }}</a>
                    @else
                        <span class="text-muted">{{ $c->website_category_name }}</span>
                    @endif
                    <small class="text-muted">({{ $c->websites->count() }})</small>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="card-body p-0 striped lightbox-gallery">
        @foreach ($websites as $website)
            <div class="row g-0 p-2 pb-4 pt-4 pt-md-3 pb-md-3">
                <div class="col-md-4">
                    @if ($website->file)
                        <a class="lightbox-link" href="{{ asset('storage/images/website_images/'.$website->file) }}">
                            <img class="w-100 cropped mb-2 mb-md-0" src="{{ asset('storage/images/website_images/'.$website->file) }}" alt="Screenshot of website {{ $website->website_name }}">
                        </a>
                    @endif
                </div>
                <div class="col-md-8 pl-2">
                    @if ($website->inactive === 1)
                        <small class="text-warning mt-1 float-right"><i class="fas fa-exclamation-triangle"></i> Appears to be inactive</small>
                    @endif

                    <h3 class="card-title fs-5 text-audiowide">
                        <a href="{{ $website->website_url }}">{{ $website->website_name }}</a>
                        @contributor
                            <a href="{{ config('al.legacy.base_url').'/admin/links/link_mod.php?website_id='.$website->website_id }}">
                                <small><i class="fas fa-pencil-alt text-contributor"></i></small>
                            </a>
                        @endcontributor
                    </h3>
                    <p class="card-subtitle text-muted">Added on {{ date('F j, Y', $website->website_date) }} by {{ Helper::user($website->user) }}</p>
                    <div class="mb-2"><small><a href="{{ $website->website_url }}">{{ $website->website_url }}</a></small></div>
                    <p class="card-text">{{ $website->description }}</p>
                </div>
            </div>
        @endforeach

        {{ $websites->withQueryString()->links() }}
    </div>
</div>
