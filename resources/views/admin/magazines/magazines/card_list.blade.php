<h2 class="card-title fs-4">Magazines</h2>

<div class="card mb-3 bg-light">
    <div class="card-body">
        <livewire:admin.magazines-table />

        <a href="{{ route('admin.magazines.magazines.create') }}" class="btn btn-success mt-3">
            <i class="fas fa-plus-square fa-fw"></i> Add a magazine
        </a>
    </div>
</div>
