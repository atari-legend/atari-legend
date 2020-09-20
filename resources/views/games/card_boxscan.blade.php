<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Boxscan</h2>
    </div>

    <div class="card-body p-2">
        @if ($boxscans->isNotEmpty())
            <div class="row">
                <div class="col">
                    <img class="w-100 mb-2" src="{{ $boxscans->first() }}" alt="Large scan of the game box">
                </div>
            </div>
            <div class="row">
                <div class="col">
                    @foreach ($boxscans->skip(1) as $boxscan)
                        <img class="w-25 mr-2" src="{{ $boxscan }}" alt="Thumbnail of other scans of the game box">
                    @endforeach
                </div>
            </div>
        @else
            <p class="card-text text-center">
                There is no boxscan in the database. You have one? Please use the
                submit card to sent it to us.
            </p>
        @endif
    </div>
    @if ($boxscans->isNotEmpty())
        <div class="card-footer text-muted">
            <strong>{{ $boxscans->count() }}</strong> {{ Str::plural('boxscan', $boxscans->count() )}} loaded
        </div>
    @endif
</div>
