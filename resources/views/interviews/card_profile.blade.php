<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Profile</h2>
    </div>

    <div class="card-body p-2">
        @if (isset($interview->individual->profile) && trim($interview->individual->profile) !== '')
            <p class="card-text">{!! Helper::bbCode(e($interview->individual->profile)) !!}</p>
        @else
            <p class="card-text text-center text-muted">There is currently no profile available in our database</p>
        @endif
    </div>
</div>
