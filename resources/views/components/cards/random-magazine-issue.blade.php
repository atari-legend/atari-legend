@if ($issue)
    <div class="card bg-dark mb-4">
        <div class="card-header text-center">
            <h2 class="text-uppercase">Random magazine</h2>
        </div>
        <div class="card-body p-0 striped">
            <img src="{{ $issue->image }}" class="img-fluid bg-black" alt="Cover for {{ $issue->label }}">
        </div>
    </div>
@endif
