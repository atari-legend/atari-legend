<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">{{ $menuset->name }}</h2>
    </div>
    <div class="card-body p-2">
        <p class="card-text text-center mb-2">
            <span class="text-muted">By: {{ $menuset->crews->pluck('crew_name')->join(', ') }}</span><br><br>
            <em>{{ $menuset->name }}</em> contains
            {{ $menuset->menus->count() }}
            {{ Str::plural('menu', $menuset->menus->count()) }}
            and
            {{ $disks->total() }}
            {{ Str::plural('disk', $menuset->menus->pluck('disks')->flatten()->count()) }}.

            <span class="ms-3">
                @if ($missingCount > 0)
                    <i class="fas fa-exclamation-triangle text-warning"></i>
                    {{ $missingCount }} {{ Str::plural('disk', $missingCount) }} missing or damaged
                    ({{ number_format(MenuHelper::percentComplete($disks->total(), $missingCount), 1) }}% complete).
                @else
                    <i class="fas fa-check text-success"></i>
                    All known disks are available, our set is 100% complete!
                @endif
            </span>

            @if ($scrollTextCount > 0)
                <br><br>
                {{ $scrollTextCount }} {{ Str::plural('scrolltext', $scrollTextCount) }} <i class="fas fa-scroll"></i>,
                <a href="{{ route('menus.epub', $menuset) }}">
                    read them all in an eBook.
                </a>
            @endif
        </p>
    </div>
</div>

<div class="row lightbox-gallery">
    @foreach ($disks as $disk)
        <div class="col-12">
            @include('menus.partial_menudisk')
        </div>
    @endforeach

    {{ $disks->links() }}
</div>

