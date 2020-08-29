<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase"><a href="{{ route('links.index') }}">Hot Links</a></h2>
    </div>
    <div class="card-body p-0">
        @if ($website->website_imgext)
        <img class="w-100" src="{{ asset('public/images/website_images/'.$website->file) }}">
        @endif
        <div class="p-2">
            <p class="card-text">{{ $website->description }}</p>
            <h6 class="card-subtitle text-muted">{{ date('F j, Y', $website->website_date) }} by {{ Helper::user($website->user) }}</h6>
            <a class="d-block text-right" href="{{ $website->website_url }}">
                More <i class="fas fa-chevron-right"></i>
            </a>
        </div>
    </div>
</div>
