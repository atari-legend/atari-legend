<div class="card bg-dark mb-4 card-link">
    <div class="card-header text-center">
        <h2 class="text-uppercase"><a href="{{ route('links.index') }}">Hot Links</a></h2>
    </div>
    <div class="card-body p-0">
        @isset ($website)
            @if ($website->file !== null)
                <figure>
                    <img class="w-100 cropped" src="{{ asset('storage/images/website_images/'.$website->file) }}" alt="Screenshot of the website {{ $website->website_name }}">
                    <figcaption class="py-2 px-3">
                        <div class="figcaption-caret"><i class="fas fa-angle-up fa-2x"></i></div>
                        <div class="figcaption-title"><a href="{{ $website->website_url }}">{{ $website->website_name }}</a></div>
                        <div class="figcaption-subtitle mb-2"><strong>Random link</strong></div>
                    </figcaption>
                </figure>
            @endif
            <div class="p-2">
                <p class="card-text">{{ $website->description }}</p>
                <p class="card-subtitle text-muted">{{ date('F j, Y', $website->website_date) }} by {{ Helper::user($website->user) }}</p>
                <a class="d-block text-end" href="{{ $website->website_url }}">
                    More <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        @endisset
    </div>
</div>
