{{-- A disk's first screenshot. With a dump it plays the disk in the emulator,
     otherwise it opens the lightbox where the card has one --}}
@php($screenshotUrl = asset('storage/images/menu_screenshots/'.$disk->screenshots->first()->file))
@if (($playOverlay ?? true) && $disk->menuDiskDump !== null)
    <x-play-screenshot :url="route('menus.emulator', ['set' => $disk->menu->menuSet, 'disk' => $disk])" :label="$disk->download_basename">
        <img class="{{ $imgClass }}" src="{{ $screenshotUrl }}" alt="{{ $alt }}" loading="lazy">
    </x-play-screenshot>
@elseif ($lightbox ?? false)
    <a class="lightbox-link" href="{{ $screenshotUrl }}" title="{{ $disk->download_basename }}">
        <img class="{{ $imgClass }}" src="{{ $screenshotUrl }}" alt="{{ $alt }}" loading="lazy">
    </a>
@else
    <img class="{{ $imgClass }}" src="{{ $screenshotUrl }}" alt="{{ $alt }}" loading="lazy">
@endif
