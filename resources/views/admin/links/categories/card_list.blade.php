<h2 class="card-title fs-4">Link Categories</h2>

<div class="card mb-3 bg-light">
    <div class="card-body">
        <livewire:admin.link-categories-table />

        <a href="{{ route('admin.links.categories.create') }}" class="btn btn-success mt-3">
            <i class="fas fa-plus-square fa-fw"></i> Add a category
        </a>
    </div>
</div>
