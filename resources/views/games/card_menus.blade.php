@if ($game->releases->filter(function($release) { return $release->menuDiskContents->isNotEmpty(); })->isNotEmpty())
    <div class="p-3 text-center">
        This game is also found in the following menus:
    </div>

    <div class="row row-cols-1 row-cols-md-3">
        @foreach ($game->releases->filter(function($release) { return $release->menuDiskContents->isNotEmpty(); }) as $release)
            @php
                $disk = $release->menuDiskContents->first()->menuDisk
            @endphp
            <div class="col d-flex">
                <div class="card mb-4 bg-dark w-100">
                    <div class="card-header text-center">
                        <h3 class="text-audiowide fs-6">
                            {{ $disk->menu->menuSet->name }}
                            #{{ $disk->menu->label}}
                            {{ $disk->part}}
                        </h3>
                    </div>


                    <div class="card-body p-0 d-flex flex-column striped">
                        <!-- div class="striped" -->
                            <figure>
                                @if ($disk->screenshots->isNotEmpty())
                                    <img class="card-img-top w-100"
                                        src="{{ asset('storage/images/menu_screenshots/'.$disk->screenshots->first()->file) }}"
                                        alt="Screenshot of disk">
                                @else
                                    <img class="card-img-top w-100 bg-black"
                                        src="{{ asset('images/no-screenshot.png') }}"
                                        alt="Screenshot of disk">
                                @endif
                            </figure>

                            <div class="px-2 flex-fill">
                                <ul class="list-unstyled ps-2">
                                    @foreach ($disk->contents->sortBy('contentName') as $content)
                                        <li>
                                            @if ($content->release)
                                                <a href="{{ route('games.releases.show', $content->release) }}">{{ $content->contentName }}</a>
                                            @elseif ($content->demozoo_id)
                                                <img src="{{ asset('images/demozoo-16x16.png') }}">
                                                <a href="https://demozoo.org/productions/{{ $content->demozoo_id }}/">{{ $content->contentName }}</a>
                                            @else
                                                {{ $content->contentName }}
                                            @endif

                                            @if ($content->notes)
                                                <small class="text-muted"><em>{{ $content->notes }}</em></small>
                                            @endif

                                            @if ($content->subtype)
                                                <small class="text-muted">[{{ $content->subtype }}]</small>
                                            @endif


                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="card-footer text-end">
                                @if ($disk->menuDiskDump !== null)
                                        @auth
                                        <a class="ms-1 text-muted" data-copy-text="{{ $disk->menuDiskDump->sha512 }}" href="javascript:;"><i class="far fa-copy"></i></a>
                                            <abbr class="text-muted d-inline-block me-2" title="{{ $disk->menuDiskDump->sha512 }}">
                                                <small>{{ Str::limit($disk->menuDiskDump->sha512, 7, '') }}</small>
                                            </abbr>

                                            <a href="{{ asset('storage/zips/menus/'.$disk->menuDiskDump->id.'.zip') }}">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        @endauth
                                        @guest
                                            <span class="text-danger">
                                                Please <a href="{{ route('login') }}">log in</a> to download
                                            </span>
                                        @endguest
                                @else
                                    <small class="text-muted">No download available</small>
                                @endif
                            </div>
                        <!-- /div -->
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
