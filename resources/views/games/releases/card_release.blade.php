<div class="card bg-dark mb-4 card-game">
    <div class="card-header text-center">
        <h2 class="text-uppercase">
            @if ($release->date !== null)
                {{ $release->date->year }}
            @else
                [no date]
            @endif
            @if ($release->name !== null && $release->name !== '')
                / {{ $release->name }}
            @endif
        </h2>
    </div>
    <div class="card-body">

    </div>
</div>
