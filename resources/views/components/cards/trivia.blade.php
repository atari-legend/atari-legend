<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">
            Did you know?
            @contributor
                <a href="{{ route('admin.others.trivias.index') }}#trivia-{{ $trivia->getKey() }}">
                    <small><i class="fas fa-pencil-alt text-contributor"></i></small>
                </a>
            @endcontributor
        </h2>
    </div>
    <div class="card-body p-2">
        @isset ($trivia)
            <p class="card-text">
                {{ $trivia->trivia_text }}
            </p>
        @endisset
    </div>
</div>
