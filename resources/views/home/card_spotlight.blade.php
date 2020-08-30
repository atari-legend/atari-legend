<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Spotlight</h2>
    </div>
    <div class="card-body p-0">
        <img class="w-100" src="{{ asset('storage/images/spotlight_screens/'.$spotlight->screenshot->file) }}">
        <p class="card-text p-2">
            {{ $spotlight->spotlight }}
            <a class="d-block text-right" href="{{ $spotlight->link }}">
                More <i class="fas fa-chevron-right"></i>
            </a>
        </p>
    </div>
</div>
