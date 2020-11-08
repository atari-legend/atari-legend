<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Profile</h2>
    </div>

    <div class="card-body p-2">
        @if (isset($interview->individual->text) && isset($interview->individual->text->ind_profile) && trim($interview->individual->text->ind_profile) !== '')
            <p class="card-text">{!! Helper::bbCode($interview->individual->text->ind_profile) !!}</p>
        @else
            <p class="card-text text-center text-muted">There is currently no profile available in our database</p>
        @endif
    </div>
</div>
