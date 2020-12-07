<div class="card card-stat position-relative overflow-hidden mb-3 bg-light">
    <div class="card-body bg-light">
        <h2 class="card-title">{{ number_format($count) }}</h2>
        <h4 class="card-subtitle text-muted ms-3">{{ $label }}</h2>
        <i class="{{ $icon ?? ''}} fa-4x text-muted position-absolute bottom-0 left-0"></i>
    </div>
</div>
