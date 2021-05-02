<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Software</h2>
    </div>
    <div class="card-body p-2">
        <form method="get" action="{{ route('menus.search') }}">
            <div class="row mb-3">
                <label for="title" class="col-4 col-sm-3 col-form-label text-nowrap">Software name</label>
                <div class="col position-relative">
                    <input type="text" class="form-control"
                        value="{{ old('title', $title) }}" required
                        id="title" name="title" autocomplete="off">
                </div>
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>
    </div>
    <div class="card-body p-2" id="results">
        @include('layouts.search_tabs', ['active' => 'software'])
        @if ($software->total() < 1)
            <p class="card-text text-muted text-center pt-3">No software found</p>
        @endif
        <div class="row pt-2">
            <div class="col">
                @foreach ($software as $soft)
                    <div class="mb-3">
                        <a href="{{ route('menus.software', $soft) }}"><h3 class="d-inline fs-5">{{ $soft->name }}</h3></a>
                        <span class="text-muted ms-1">{{ $soft->menuSoftwareContentType->name }}</span>

                        @if ($soft->demozoo_id)
                            <a class="ms-1" href="https://demozoo.org/productions/{{ $soft->demozoo_id }}">
                                <img src="{{ asset('images/demozoo-16x16.png') }}" class="border-0" alt="Demozoo link for {{ $soft->name }}">
                            </a>
                        @endif
                    </div>
                @endforeach

                {{ $software->withQueryString()->fragment('results')->links() }}
            </div>
        </div>

    </div>
</div>
