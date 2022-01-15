<h2 class="card-title fs-4">News</h2>

<div class="card mb-3 bg-light">
    <div class="card-body">
        <livewire:admin.news-table />

        <a href="{{ route('admin.news.news.create') }}" class="btn btn-success">
            <i class="fas fa-plus-square fa-fw"></i> Add a news
        </a>

    </div>
</div>
