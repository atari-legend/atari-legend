<h2 class="card-title fs-4">Issues</h2>

<div class="card mb-3 bg-light">
    <div class="card-body">
        <livewire:admin.magazine-issues-table :magazine="$magazine->id" />

        <a href="{{ route('admin.magazines.issues.create', ['magazine' => $magazine]) }}" class="btn btn-success mt-3">
            <i class="fas fa-plus-square fa-fw"></i> Add an issue
        </a>
    </div>
</div>
