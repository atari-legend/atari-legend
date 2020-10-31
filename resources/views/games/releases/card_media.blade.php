<div class="card bg-dark mb-4 card-game">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Media & Downloads</h2>
    </div>

    @if ($release->medias->isNotEmpty())
        <div class="striped">
            @foreach ($release->medias as $media)
                <div class="card-body">
                    @guest
                        @if ($loop->first)
                            <p class="float-right text-danger">
                                Please <a href="{{ route('login') }}">log-in</a> to download files
                            </p>

                        @endif
                    @endguest
                    <div class="mb-2">
                        @isset ($mediaTypeIcons[$media->type->id])
                            <i class="{{ $mediaTypeIcons[$media->type->id] }} mr-1"></i>
                        @endif
                        <span class="badge bg-secondary">{{ $media->type->name }}</span>
                        @isset ($media->label) {{ $media->label }} @endif
                    </div>

                    @if ($media->dumps->isNotEmpty())
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Format</th>
                                    <th>SHA-512</th>
                                    <th>Size</th>
                                    <th>Added</th>
                                    <th>By</th>
                                    <th>Info</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($media->dumps as $dump)
                                    <tr>
                                        <td>
                                            @auth
                                                <a href="{{ asset('storage/zips/games/'.$dump->id.'.zip') }}">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            @endauth
                                            @guest
                                                <i class="fas fa-download text-muted"></i>
                                            @endguest
                                        </td>
                                        <td>{{ $dump->format }}</td>
                                        <td>
                                            <abbr title="{{ $dump->sha512 }}">
                                                {{ Str::limit($dump->sha512, 7, '') }}
                                            </abbr>
                                        </td>
                                        <td>{{ Helper::fileSize($dump->size) }}</td>
                                        <td>{{ date('F j, Y', $dump->date) }}
                                        <td>{{ Helper::user($dump->user )}}</td>
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
