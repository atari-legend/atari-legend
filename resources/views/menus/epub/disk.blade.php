@include('menus.epub.header')

<h1>Disk {{ $disk->menu->label }}{{ $disk->label }}</h1>

@if ($disk->menu->date)
    <h2>{{ $disk->menu->date->format('F j, Y') }}</h2>
@endif

@if ($disk->screenshots->isNotEmpty())
    <div class="screenshot">
        <img src="images/{{ $disk->screenshots->first()->file }}" alt="Screenshot of menu" />
    </div>
@endif

@if ($disk->contents->isNotEmpty())
    <h3>Disk content:</h3>

    <ul>
        @foreach ($disk->contents->sortBy('order') as $content)
            @include('menus.partial_menudisk_content', ['demozoo_icon_url' => 'images/demozoo.png'])
        @endforeach
    </ul>
@else
    <h3>[Unknown content]</h3>
@endif

@if ($disk->scrolltext !== null)
    <p><code class="monospace">{!! nl2br(e($disk->scrolltext), false) !!}</code></p>
@else
    <p class="no-scrolltext">No scrolltext for this disk</p>
@endif

@include('menus.epub.footer')
