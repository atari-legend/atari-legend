<div class="card mb-3 bg-light">
    <div class="card-body">

        <h2 class="card-title fs-4">Index</h2>

        @foreach ($issue->indexes as $index)
            <p>
                {{ $index->game?->game_name ?? 'no game' }} {{ $index->score }}
            </p>
        @endforeach
    </div>
</div>
