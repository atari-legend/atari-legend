@include('menus.epub.header')

<div class="cover">
    <h1>{{ $set->name }}</h1>

    <h2>by {{ $set->crews->pluck('crew_name')->join(', ') }}</h2>

    <p>
        This eBook contains information and scrolltexts from the menu disks.
        It has been generated from the AtariLegend database. Please consider
        visiting the site and contributing missing data.
    </p>

    <p>
        <a href="{{ URL::to('/') }}">www.atarilegend.com</a>
    </p>

    <p>
        <small>Generated {{ Carbon\Carbon::now()->format('F j, Y H:i') }}</small>
    </p>

</div>

@include('menus.epub.footer')
