<h2 class="card-title fs-4">Games</h2>

<div class="card mb-3 bg-light">
    <div class="card-body">
        <livewire:admin.games.games-table />

        <a href="{{ route('admin.games.games.create') }}" class="btn btn-success">
            <i class="fas fa-plus-square fa-fw"></i> Add a new game
        </a>
    </div>
</div>
