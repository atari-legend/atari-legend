<div class="card mb-4 w-100">
    <div class="card-body">
        <a href="{{ route('admin.menus.disks.content.edit', ['disk' => $disk, 'content' => $content]) }}" title="Remove from disk" class="btn btn-link float-end p-0">
            <i class="fas fa-pencil-alt fa-fw" aria-hidden="true"></i>
        </a>

        <h5 class="card-title fs-6"><span class="text-muted">{{$content->order}}.</span> {{ $content->label }}</h5>

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
        <form action="{{ route('admin.menus.disks.content.destroy', ['disk' => $disk, 'content' => $content]) }}"
            method="POST"
            onsubmit="javascript:return confirm('This content will be permanently deleted')">
            @csrf
            @method('DELETE')
            <button title="Remove from disk" class="btn float-end p-0">
                <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
            </button>
        </form>
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
