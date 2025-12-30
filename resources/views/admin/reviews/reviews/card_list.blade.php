<h2 class="card-title fs-4">Reviews</h2>

<div class="card mb-3 bg-light">
    <div class="card-body">
        <livewire:admin.reviews-table :submissions="\App\Models\Review::REVIEW_PUBLISHED" />

        <a href="{{ route('admin.reviews.reviews.create') }}" class="btn btn-success">
            <i class="fas fa-plus-square fa-fw"></i> Add a review
        </a>

    </div>
</div>
