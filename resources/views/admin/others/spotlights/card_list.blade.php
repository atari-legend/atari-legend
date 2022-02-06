<h2 class="card-title fs-4">Spotlights</h2>

<div class="card mb-3 bg-light">
    <div class="card-body">
        <livewire:admin.spotlights-table />

        <a href="{{ route('admin.others.spotlights.create') }}" class="btn btn-success mt-3">
            <i class="fas fa-plus-square fa-fw"></i> Add a spotlight
        </a>

    </div>
</div>
