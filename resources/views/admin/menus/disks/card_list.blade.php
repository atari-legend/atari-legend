<h2 class="fs-4">{{ count($disks) }} disks in this menu</h2>

<div class="row row-cols row-cols-md-3 row-cols-lg-4 row-cols-xl-5 gy-4 mb-3">
    @foreach ($disks as $disk)
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <div class="float-end">
                        @isset ($disk->scrolltext)
                            <i class="fas fa-file-alt fa-fw text-muted" title="Has scrolltext"></i>
                        @endif

                        @isset ($disk->scrolltext)
                            <i class="fas fa-comment fa-fw text-muted" title="Has comments"></i>
                        @endif

                        @if ($disk->screenshots->isNotEmpty())
                            <i class="fas fa-camera fa-fw text-muted" title="Has screenshots"></i>
                            <small class="text-muted">&times; {{ $disk->screenshots->count() }}</small>
                        @endif
                    </div>
                    @isset($disk->part)
                        <h3 class="card-title fs-5">
                            <a href="{{ route('admin.menus.disks.edit', $disk) }}">Part {{ $disk->part }}</a>
                        </h3>
                    @endif
                </div>
                @if ($disk->screenshots->isNotEmpty())
                    <img class="card-img-top w-100"
                        src="{{ asset('storage/images/menu_screenshots/'.$disk->screenshots->first()->file) }}"
                        alt="Screenshot of disk">
                @else
                    <img class="card-img-top w-100 bg-dark"
                        src="{{ asset('images/no-screenshot.png') }}"
                        alt="Screenshot of disk">
                @endif
                <div class="card-body">
                    <small class="text-muted">
                        Created: {{ $disk->created_at ? $disk->created_at->diffForHumans() : '-' }}<br>
                        Updated: {{ $disk->updated_at ? $disk->updated_at->diffForHumans() : '-' }}
                    </small>
                </div>
                <div class="card-footer">
                    <div>
                            <form action="{{ route('admin.menus.disks.destroy', $disk) }}"
                                method="POST"
                                onsubmit="javascript:return confirm('This item will be permanently deleted')">
                                @csrf
                                @method('DELETE')
                                <a class="btn btn-primary"><i class="fas fa-pen"></i> Edit</a>
                                <button title="Delete disk '{{ $disk->part }}'" class="btn float-end">
                                    <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
                                </button>
                            </form>

                    </div>
                </div>

            </div>
        </div>
    @endforeach
</div>

<a href="{{ route('admin.menus.disks.create', ['menu' => $menu]) }}" class="btn btn-success">
    <i class="fas fa-plus-square fa-fw"></i> Add a new menu to this set
</a>
