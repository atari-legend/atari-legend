<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Magazines</h2>
    </div>
    <div class="card-body p-2">
        <p class="card-text mb-3">
            Please find in this section all the magazines that had a connection
            with the Atari world. There are currently {{ count($magazines) }}
            magazines in the database.
        </p>
    </div>
    <div class="card-body p-2 row row-cols-2 row-cols-md-4">
        @foreach ($magazines as $magazine)
            <div class="col text-center mb-5">
                <a href="{{ route('magazines.show', $magazine) }}">
                    <img src="{{ $magazine->cover_url }}" class="card-img bg-black" alt="Cover for {{ $magazine->name }}">
                </a>
                <h3 class="fs-4 mt-2"><a href="{{ route('magazines.show', $magazine) }}">{{ $magazine->name }}</a></h3>
                <div class="text-muted">
                    {{ $magazine->issues->count() }} {{ Str::plural('issue', $magazine->issues->count()) }}

                    @if ($magazine->location?->country_iso2 !== null)
                        <span title="{{ $magazine->location->name }}"
                            class="fi fi-{{ strtolower($magazine->location->country_iso2) }} ms-1"></span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
