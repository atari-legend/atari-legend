<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">{{ $software->name }}</h2>
    </div>
    <div class="card-body p-2">
        <p class="card-text">
            <em>{{ $software->name }}</em> ({{$software->menuSoftwareContentType->name}}) is present on {{ $menuDisks->count() }} {{ Str::plural('menudisk', $menuDisks->count()) }}.
            @if ($software->demozoo_id)
                More information on <em>{{ $software->name }}</em> at <a href="https://demozoo.org/productions/{{ $software->demozoo_id }}">
                    <img src="{{ asset('images/demozoo-16x16.png') }}" alt="Demozoo link for {{ $software->name }}"> Demozoo
                </a>.
            @endif
        </p>
    </div>
</div>

<div class="row lightbox-gallery">
    @foreach($menuDisks as $disk)
        <div class="col-12">
            @include('menus.partial_menudisk')
        </div>
    @endforeach
</div>
