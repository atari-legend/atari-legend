<div class="card bg-dark mb-4 border-top-0" id="menudisk-{{ $disk->id }}">
    <div class="card-header">
        <h3 class="fs-5 mb-0">
            @if ($disk->scrolltext !== null)
                <a href="javascript:;" class="float-end d-inline-block text-primary ms-3" data-bs-toggle="collapse"
                    data-bs-target="#scrolltext-{{ $disk->id }}" role="button" aria-expanded="false">
                    <i class="fas fa-scroll fa-fw" title="Has scrolltext"></i>
                    <small class="text-smallcaps">txt</small>
                </a>
            @endif

            @if ($disk->menu->date)
                <small class="text-muted float-end">{{ $disk->menu->date->format('F j, Y') }}</small>
            @endif

            {{-- Only link to the menuset if we're not displaying the menu set
                (e.g. when listing menus for a software) --}}
            @if (!isset($menuset))
                <a href="{{ route('menus.show', ['set' => $disk->menu->menuSet, 'page' => $disk->menuset_page_number]) }}#menudisk-{{ $disk->id }}"
                    class="text-primary">
                    {{ $disk->menu->menuSet->name }}
                    {{ $disk->menu->label }}{{ $disk->label }}
                </a>
            @else
                {{ $disk->menu->menuSet->name }}
                {{ $disk->menu->label }}{{ $disk->label }}
            @endif

            <a href="{{ route('menus.show', ['set' => $disk->menu->menuSet, 'page' => Request::input('page')]) }}#menudisk-{{ $disk->id }}"
                class="ms-2 menu-link">
                <i class="fas fa-link text-muted fs-6"></i>
            </a>
        </h3>
    </div>
    <div class="card-body p-0 row g-0">
        <div class="col-12 col-sm-3">
            <div class="p-2">
            @if ($disk->screenshots->isNotEmpty())
                <a class="lightbox-link"
                    href="{{ asset('storage/images/menu_screenshots/'.$disk->screenshots->first()->file) }}"
                    title="{{ $disk->menu->menuSet->name }} {{ $disk->menu->label }}{{ $disk->part }}">
                    <img class="card-img-top w-100"
                        src="{{ asset('storage/images/menu_screenshots/'.$disk->screenshots->first()->file) }}"
                        alt="Screenshot of disk">
                </a>
            @else
                <img class="card-img-top w-100 bg-black"
                    src="{{ asset('images/no-screenshot.png') }}"
                    alt="No screenshot for this disk">
            @endif
            </div>
        </div>
        <div class="col-12 col-sm-9">
            <div class="row p-2 mb-2">
                @if ($disk->contents->isEmpty())
                    <span class="text-muted">Unknown content</span>
                @else
                    <ul class="list-unstyled col-12 @if ($disk->contents->count() > 1) col-sm-6 @endif mb-0">
                        @foreach ($disk->contents->sortBy('order')->split(2)->first() as $content)
                            @include('menus.partial_menudisk_content')
                        @endforeach
                    </ul>

                    @if ($disk->contents->count() > 1)
                        <ul class="list-unstyled col-12 col-sm-6 mb-0">
                            @foreach ($disk->contents->sortBy('order')->split(2)->last() as $content)
                                @include('menus.partial_menudisk_content')
                            @endforeach
                        </ul>
                    @endif
                @endif

                @if ($disk->notes !== null)
                    <p class="mt-2">
                        <span class="text-muted">Notes: </span>{{ $disk->notes }}
                    </p>
                @endif
            </div>
        </div>
    </div>
    @if ($disk->scrolltext !== null)
        <div class="collapse p-2 my-2 bg-black border border-secondary" id="scrolltext-{{ $disk->id }}">
            <code class="text-white">{{ $disk->scrolltext }}</code>
            <button type="button" class="btn float-end text-primary"
                data-bs-toggle="collapse"
                data-bs-target="#scrolltext-{{ $disk->id }}"><i class="fas fa-2x fa-times"></i></button>
        </div>
    @endif
    <div class="card-footer bg-darklight">
        <div class="row">
            <div class="col text-{{ $conditionClasses[$disk->menuDiskCondition->id] }}">
                <span class="text-muted">Condition:</span> {{ Str::lower($disk->menuDiskCondition->name) }}
            </div>
            <div class="col text-center">
                @if ($disk->donatedBy !== null)
                    <span class="text-muted">Donated by:</span>
                    @if ($disk->donatedBy->games->isNotEmpty())
                        <a href="{{ route('games.search', ['individual_id' => $disk->donatedBy->ind_id]) }}">
                            {{ $disk->donatedBy->public_nick }}
                        </a>
                    @else
                        {{ $disk->donatedBy->public_nick }}
                    @endif
                @endif
            </div>
            <div class="col text-end">
                @if ($disk->menuDiskDump !== null)
                    @auth
                        <a class="ms-1 text-muted" data-copy-text="{{ $disk->menuDiskDump->sha512 }}" href="javascript:;"><i class="far fa-copy"></i></a>
                        <abbr class="text-muted d-inline-block" title="{{ $disk->menuDiskDump->sha512 }}">
                            <small>{{ Str::limit($disk->menuDiskDump->sha512, 7, '') }}</small>
                        </abbr>

                        <a class="d-inline-block ms-2 me-2"
                            href="{{ asset('storage/zips/menus/'.$disk->menuDiskDump->id.'.zip') }}"
                            download="{{ $disk->download_filename}}">
                            <i class="fas fa-download"></i>
                        </a>

                        <small class="text-muted">{{ Helper::fileSize($disk->menuDiskDump->size) }}</small>
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
        </div>
    </div>
</div>
