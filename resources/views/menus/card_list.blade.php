<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Menus</h2>
    </div>
    <div class="card-body p-2">
        <p class="card-text">
            Welcome to the menus section! This is the place to find all the
            menus of your favorite Atari ST crews. There are currently
            {{ count($menusets) }} menu sets in the database.
        </p>
    </div>
    <div class="card-body p-2">
        <form method="get" action="{{ route('menus.search') }}">
            <div class="row mb-4">
                <div class="col-lg-6 offset-lg-3">
                    <div class="input-group">
                        <input type="text" class="form-control"
                            placeholder="Search for menu content (e.g. 'ripper')"
                            id="title" name="title" autocomplete="off">
                        <button type="submit" class="btn btn-primary">Search</button>
                    </div>
                </div>
            </div>
        </form>
        <div class="row mb-2 text-center">
            <ul class="list-inline menu-index">
                <li class="list-inline-item mx-0 my-1 active">
                    <a href="#" class="m-1" data-isotope-filter="*">All</a>
                </li>
                @foreach (range('A', 'Z') as $letter)
                    <li class="list-inline-item mx-0 my-0">
                        <a href="#" class="m-1" data-isotope-filter=".letter-{{ Str::lower($letter) }}">{{ $letter }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="isotope">
            @foreach ($menusets as $set)
                <div class="col-12 col-sm-6 col-md-4 p-2 letter-{{ Str::lower(Str::substr($set->name, 0, 1)) }}">
                    <div class="d-flex bg-black border border-primary-dark">
                        <div class="bg-black text-center fs-1 text-audiowide menuset-letter my-auto">{{ Str::upper(Str::substr($set->name, 0, 1)) }}</div>
                        <div class="w-100 px-1 py-2 bg-darklight">
                            <div class="text-center">
                                <a class="fs-6" href="{{ route('menus.show', [$set->id]) }}">{{ $set->name }}</a><br>
                                <small class="text-muted">{{ ($set->disks - $set->missing) }} / {{ $set->disks }} disks</small>

                                <div class="progress mt-3 mx-2 bg-black progress-menu">
                                    <div class="progress-bar text-dark bg-menu-percent" role="progressbar"
                                        style="width: {{ MenuHelper::percentComplete($set->disks, $set->missing) }}%"
                                        aria-valuenow="{{ MenuHelper::percentComplete($set->disks, $set->missing) }}" aria-valuemin="0" aria-valuemax="100">
                                        @if ($set->missing === 0)
                                            <span>Complete set <i class="fas fa-check fa-fw text-success"></i></span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
