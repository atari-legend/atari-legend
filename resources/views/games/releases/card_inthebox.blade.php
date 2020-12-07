@if ($release->hasGoodies)
    <div class="card bg-dark mb-4 card-game">
        <div class="card-header text-center">
            <h2 class="text-uppercase">In the box</h2>
        </div>

        <div class="card-body lightbox-gallery d-flex">
            @foreach ($release->boxscans as $boxscan)
                @if (!Str::startsWith($boxscan->type, 'Box'))
                    <div class="col-3 col-sm-2 me-4 text-center text-muted">
                        <a class="lightbox-link" href="{{ asset('storage/images/game_release_scans/'.$boxscan->file) }}">
                            <img class="w-100 mb-1" src="{{ asset('storage/images/game_release_scans/'.$boxscan->file) }}">
                        </a>
                        {{ $boxscan->notes }}
                    </div>
                @endif
            @endforeach
        </div>
    </div>
@endif
