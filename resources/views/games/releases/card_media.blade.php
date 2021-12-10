<div class="card bg-dark mb-4 card-game">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Media & Downloads</h2>
    </div>

    @if ($release->medias->isNotEmpty())
        <div class="striped">
            @foreach ($release->medias as $media)
                <div class="card-body">
                    <div class="mb-2 d-flex">
                        @isset ($mediaTypeIcons[$media->type->id])
                            <i class="{{ $mediaTypeIcons[$media->type->id] }} me-1"></i>
                        @endif
                        <span class="badge bg-secondary">{{ $media->type->name }}</span>
                        @isset ($media->label) <span class="ms-1">{{ $media->label }}</span> @endif
                    </div>

                    @if ($media->dumps->isNotEmpty())
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Format</th>
                                    <th class="d-none d-sm-table-cell">SHA-512</th>
                                    <th>Size</th>
                                    <th class="d-none d-sm-table-cell">Added</th>
                                    <th class="d-none d-sm-table-cell">By</th>
                                    <th>Info</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($media->dumps as $dump)
                                    <tr>
                                        <td>
                                            <a href="{{ asset('storage/zips/games/'.$dump->id.'.zip') }}"
                                                download="{{ $dump->download_filename }}">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </td>
                                        <td>{{ $dump->format }}</td>
                                        <td class="d-none d-sm-table-cell">
                                            <abbr title="{{ $dump->sha512 }}">
                                                {{ Str::limit($dump->sha512, 7, '') }}
                                                <a class="ms-1" data-copy-text="{{ $dump->sha512 }}" href="javascript:;"><i class="far fa-copy"></i></a>
                                            </abbr>
                                        </td>
                                        <td>{{ Helper::fileSize($dump->size) }}</td>
                                        <td class="d-none d-sm-table-cell">{{ date('F j, Y', $dump->date) }}
                                        <td class="d-none d-sm-table-cell">{{ Helper::user($dump->user )}}</td>
                                        <td>{{ $dump->info }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="card-text text-muted">
                            No download for this media.
                        </p>
                    @endif

                    @if ($media->scans->isNotEmpty())
                        <div class="lightbox-gallery d-flex mt-4">
                            @foreach ($media->scans as $scan)
                                <div class="col-3 col-sm-2 me-4 text-center text-muted">
                                    <a class="lightbox-link" href="{{ asset('storage/images/media_scans/'.$scan->file) }}">
                                        <img class="w-100 mb-1" src="{{ asset('storage/images/media_scans/'.$scan->file) }}">
                                    </a>
                                    {{ $scan->type->name ?? '' }}
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="card-body">
            <p class="card-text text-center text-muted">
                No media or download for this release. Please consider uploading
                one via the Submit Info card.
            </p>
        </div>
    @endif
</div>
