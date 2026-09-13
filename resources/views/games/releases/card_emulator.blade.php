<div class="card bg-dark mb-4 card-emulator" data-emulator data-tos-url="{{ $tosUrl }}"
    data-disk-url="{{ $dump->download_url }}" data-disk-id="{{ $dump->getKey() }}">
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
                @foreach ($disks as $disk)
                    <button type="button" class="btn btn-outline-primary btn-sm @if ($disk['dump']->is($dump)) active @endif"
                        data-emulator-disk="{{ $disk['dump']->getKey() }}" data-disk-url="{{ $disk['dump']->download_url }}"
                        aria-pressed="{{ $disk['dump']->is($dump) ? 'true' : 'false' }}" disabled>
                        <i class="far fa-floppy-disk me-1"></i>{{ $disk['label'] }}
                    </button>
                @endforeach
            </div>
        @endif

        <p class="card-text text-muted small">
            Click the screen to focus the keyboard. The cursor keys move the joystick and right Ctrl fires.
        </p>
    </div>

    <div class="emulator-screen m-2" data-emulator-screen>
        {{-- Hatari's SDL layer looks its screen up by this id --}}
        <canvas id="canvas" width="640" height="400" tabindex="0" aria-label="Atari ST screen"></canvas>
    </div>
</div>
