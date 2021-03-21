<div class="card mb-3 bg-light">
    <div class="card-body">

        @if ($disk !== null && $disk->menuDiskDump !== null)
            <form action="{{ route('admin.menus.disks.destroyDump', ['disk' => $disk, 'dump' => $disk->menuDiskDump]) }}" method="POST"
                onsubmit="javascript:return confirm('This dump will be removed')">
                @csrf
                @method('DELETE')

                <button title="Remove dump" class="btn float-end ps-2 p-0">
                    <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
                </button>
            </form>
            <a class="float-end" download="{{ $disk->download_filename}}"
                href="{{ asset('storage/zips/menus/'.$disk->menuDiskDump->id.'.zip') }}">
                <i class="fas fa-download"></i>
            </a>
        @endif

        <h2 class="card-title fs-4">Dump</h2>

        @if ($disk !== null && $disk->menuDiskDump !== null)
            <ul class="list-unstyled mb-0">
                <li><span class="text-muted">Format:</span> {{ $disk->menuDiskDump->format }}</li>
                <li><span class="text-muted">SHA-512:</span> <abbr title="{{ $disk->menuDiskDump->sha512 }}">{{ Str::limit($disk->menuDiskDump->sha512, 7, '') }}</abbr></li>
                <li><span class="text-muted">Size:</span> {{ Helper::fileSize($disk->menuDiskDump->size) }}</li>
                <li>
                    <span class="text-muted">Uploaded by:</span>
                    {{ $disk->menuDiskDump->user->userid }}
                    <span class="text-muted">on</span>
                    {{ $disk->menuDiskDump->created_at->format("F    j, Y") }}
                </li>
            </ul>
        @endif

    <form action="{{ route('admin.menus.disks.storeDump', $disk) }}" method="post" enctype="multipart/form-data">
        @csrf
        <div class="row mt-2">
            <div class="col-9">
                <input type="file" class="form-control w-100" name="dump">
            </div>
            <div class="col-3">
                <button type="submit" class="btn btn-primary w-100">
                    @if ($disk !== null && $disk->menuDiskDump !== null)
                        Replace
                    @else
                        Add
                    @endif
                </button>
            </div>
        </div>
    </form>
    </div>
</div>
