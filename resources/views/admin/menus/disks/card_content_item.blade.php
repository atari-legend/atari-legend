<div class="card mb-4 w-100">
    <div class="card-body">
        <a href="" title="Remove from disk" class="btn btn-link float-end p-0">
            <i class="fas fa-pencil-alt fa-fw" aria-hidden="true"></i>
        </a>
        @if ($content->game)
            <h5 class="card-title fs-6"><span class="text-muted">{{$content->order}}.</span> {{ $content->game->game_name }}</h5>
        @elseif ($content->release)
            <h5 class="card-title fs-6"><span class="text-muted">{{$content->order}}.</span> {{ $content->release->game->game_name }}</h5>
        @elseif ($content->menuSoftware)
            <h5 class="card-title fs-6"><span class="text-muted">{{$content->order}}.</span> {{ $content->menuSoftware->name }}</h5>
        @else
            <h5 class="card-title text-danger">Unknown or empty content!</h5>
        @endif

        <p class="card-text">
            <ul class="list-unstyled">
                @if ($content->subtype)
                    <li><span class="text-muted">Subtype:</span> {{ $content->subtype }}</li>
                @endif
                @if ($content->version)
                    <li><span class="text-muted">Version:</span> {{ $content->version }}</li>
                @endif
                @if ($content->requirements)
                    <li><span class="text-muted">Requirements:</span> {{ $content->requirements }}</li>
                @endif
            </ul>
        </p>
    </div>
    <div class="card-footer">
        <button title="Remove from disk" class="btn float-end p-0">
            <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
        </button>
        @if ($content->game)
            <h6 class="text-muted mt-1 mb-0">
                <small><i class="fas fa-gamepad fa-fw me-1"></i> Game doc & extra</small>
            </h6>
        @elseif ($content->release)
            <h6 class="text-muted mt-1 mb-0">
                <small><i class="fas fa-box-open fa-fw me-1"></i> Game release</small>
            </h6>
        @elseif ($content->menuSoftware)
            <h6 class="text-muted mt-1 mb-0">
                <small><i class="fas fa-tools fa-fw me-1"></i> Software</small>
            </h6>
        @else
            <h6 class="text-danger">Unknown or empty content!</h5>
        @endif
    </div>
</div>
