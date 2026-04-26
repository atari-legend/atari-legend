<h2 class="card-title fs-4">Links</h2>

<div class="card mb-3 bg-light">
    <div class="card-body">
        <livewire:admin.links-table />

        <a href="{{ route('admin.links.links.create') }}" class="btn btn-success mt-3">
            <i class="fas fa-plus-square fa-fw"></i> Add a link
        </a>
    </div>
</div>
