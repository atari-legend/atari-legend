<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Spotlight</h2>
    </div>
    <div class="card-body p-0">
        @isset ($spotlight)
            <img class="w-100" src="{{ asset('storage/images/spotlight_screens/'.$spotlight->screenshot->file) }}" alt="Spotlight image">
            <p class="card-text p-2">
                {{ $spotlight->spotlight }}
                <a class="d-block text-end" href="{{ $spotlight->link }}">
                    More <i class="fas fa-chevron-right"></i>
                </a>
            </p>
        @endisset
    </div>
</div>
