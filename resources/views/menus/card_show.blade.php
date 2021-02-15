<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">{{ $menuset->name }}</h2>
    </div>
    <div class="card-body p-2">
        <p class="card-text text-center">
            <em>{{ $menuset->name }}</em> contains
            {{ $menuset->menus->count() }}
            {{ Str::plural('menu', $menuset->menus->count()) }}
            and
            {{ $menuset->menus->pluck('disks')->flatten()->count() }}
            {{ Str::plural('disk', $menuset->menus->pluck('disks')->flatten()->count()) }}.
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

