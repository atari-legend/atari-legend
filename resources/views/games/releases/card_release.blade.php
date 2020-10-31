<div class="card bg-dark mb-4 card-game">
    <div class="card-header text-center">
        <h2 class="text-uppercase">
            {{ $release->year }}
            @if ($release->name !== null && $release->name !== '')
                / {{ $release->name }}
            @endif
        </h2>
    </div>
    <div class="card-body">
        @foreach ($descriptions as $description)
            <p class="card-text">{{ $description }}</p>
        @endforeach
    </div>
</div>
