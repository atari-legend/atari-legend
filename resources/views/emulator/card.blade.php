@php($currentDisk = $disks->firstWhere('current', true))
<div class="card bg-dark mb-4 card-emulator" data-emulator data-tos-url="{{ $tosUrl }}"
    data-disk-url="{{ $currentDisk['url'] }}" data-disk-id="{{ $currentDisk['id'] }}">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Emulator</h2>
    </div>

    <div class="card-body">
        <p class="card-text mb-2" role="status" data-emulator-status>Loading the emulator…</p>

        <div class="d-flex flex-wrap gap-2 mb-2">
            <button type="button" class="btn btn-primary btn-sm" data-emulator-action="warm-reset" disabled>
                <i class="fas fa-rotate-right me-1"></i>Warm reset
            </button>
            <button type="button" class="btn btn-primary btn-sm" data-emulator-action="cold-reset" disabled>
                <i class="fas fa-power-off me-1"></i>Cold reset
            </button>
            <button type="button" class="btn btn-primary btn-sm" data-emulator-action="fullscreen" disabled>
                <i class="fas fa-expand me-1"></i>Fullscreen
            </button>
            <button type="button" class="btn btn-primary btn-sm" data-emulator-action="mute" aria-pressed="false" disabled>
                <i class="fas fa-volume-high me-1"></i>Mute
            </button>
        </div>

        @if ($disks->count() > 1)
            <div class="d-flex flex-wrap gap-2 mb-2" role="group" aria-label="Disk in drive A">
                @foreach ($disks as $diskButton)
                    <button type="button" class="btn btn-outline-primary btn-sm @if ($diskButton['current']) active @endif"
                        data-emulator-disk="{{ $diskButton['id'] }}" data-disk-url="{{ $diskButton['url'] }}"
                        aria-pressed="{{ $diskButton['current'] ? 'true' : 'false' }}" disabled>
                        <i class="far fa-floppy-disk me-1"></i>{{ $diskButton['label'] }}
                    </button>
                @endforeach
            </div>
        @endif

        <p class="card-text text-muted small">
            Click the screen to focus the keyboard. The cursor keys move the joystick and right Ctrl fires.
        </p>
    </div>

    <div class="emulator-screen m-2" style="{{ $bezelStyle }}" data-emulator-screen>
        {{-- Hatari's SDL layer looks its screen up by this id --}}
        <canvas id="canvas" width="640" height="400" tabindex="0" aria-label="Atari ST screen"></canvas>
    </div>

    <div class="card-footer text-muted text-center">
        <small>Hatari WebAssembly build by <a href="http://absencehq.de/atariaviary/">Atariaviary</a>.</small>
    </div>
</div>
