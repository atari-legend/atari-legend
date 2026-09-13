@props(['url', 'label'])

{{-- A screenshot that plays in the emulator, with a play triangle over it --}}
<a {{ $attributes->merge(['class' => 'play-screenshot']) }} href="{{ $url }}"
    title="Play in the emulator" aria-label="Play {{ $label }} in the emulator">
    {{ $slot }}
    <i class="fas fa-play play-screenshot-overlay"></i>
</a>
