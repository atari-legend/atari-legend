<h2 class="card-title fs-4">News</h2>

<div class="card mb-3 bg-light">
    <div class="card-body">
        <livewire:admin.articles-table />

        <a href="{{ route('admin.articles.articles.create') }}" class="btn btn-success">
            <i class="fas fa-plus-square fa-fw"></i> Add an article
        </a>

    </div>
</div>
