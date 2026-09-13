{{-- A disk's first screenshot. With a dump it plays the disk in the emulator,
     otherwise it opens the lightbox where the card has one --}}
@php($screenshotUrl = asset('storage/images/menu_screenshots/'.$disk->screenshots->first()->file))
@if (($playOverlay ?? true) && $disk->menuDiskDump !== null)
    <a class="menu-screenshot" href="{{ route('menus.emulator', ['set' => $disk->menu->menuSet, 'disk' => $disk]) }}"
        title="Play in the emulator" aria-label="Play {{ $disk->download_basename }} in the emulator">
        <img class="{{ $imgClass }}" src="{{ $screenshotUrl }}" alt="{{ $alt }}" loading="lazy">
        <i class="fas fa-play menu-play-overlay"></i>
    </a>
@elseif ($lightbox ?? false)
    <a class="lightbox-link" href="{{ $screenshotUrl }}" title="{{ $disk->download_basename }}">
        <img class="{{ $imgClass }}" src="{{ $screenshotUrl }}" alt="{{ $alt }}" loading="lazy">
    </a>
@else
    <img class="{{ $imgClass }}" src="{{ $screenshotUrl }}" alt="{{ $alt }}" loading="lazy">
@endif
