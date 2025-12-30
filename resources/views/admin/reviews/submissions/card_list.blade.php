<h2 class="card-title fs-4">Reviews Submissions</h2>

<div class="card mb-3 bg-light">
    <div class="card-body">
        <livewire:admin.reviews-table :submissions="\App\Models\Review::REVIEW_UNPUBLISHED" />
    </div>
</div>
