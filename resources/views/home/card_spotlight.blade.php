<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">
            Spotlight
            @contributor
                <a href="{{ route('admin.others.spotlights.edit', $spotlight) }}">
                    <small><i class="fas fa-pencil-alt text-contributor"></i></small>
                </a>
            @endcontributor
        </h2>
    </div>
    <div class="card-body p-0">
        @isset ($spotlight)
            @isset ($spotlight->screenshot)
                <img class="w-100" src="{{ asset('storage/images/spotlight_screens/'.$spotlight->screenshot->file) }}" alt="Spotlight image">
            @endisset
            <p class="card-text p-2">
                {{ $spotlight->spotlight }}
                <a class="d-block text-end" href="{{ $spotlight->link }}">
                    More <i class="fas fa-chevron-right"></i>
                </a>
            </p>
        @endisset
    </div>
</div>
