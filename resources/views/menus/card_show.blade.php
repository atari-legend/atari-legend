<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">{{ $menuset->name }}</h2>
    </div>
    <div class="card-body p-2">
        <p class="card-text text-center mb-2">
            <em>{{ $menuset->name }}</em> contains
            {{ $menuset->menus->count() }}
            {{ Str::plural('menu', $menuset->menus->count()) }}
            and
            {{ $disks->total() }}
            {{ Str::plural('disk', $menuset->menus->pluck('disks')->flatten()->count()) }}.

            <span class="ms-3">
                @if ($missingCount > 0)
                    <i class="fas fa-exclamation-triangle text-warning"></i>
                    {{ $missingCount }} disks missing
                    ({{ number_format((($disks->total() - $missingCount) / $disks->total() * 100), 0) }}% complete).
                @else
                    <i class="fas fa-check text-success"></i>
                    All disks are available, set is 100% complete!
                @endif
            </span>
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

