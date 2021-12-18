<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Tops</h2>
    </div>
    <div class="card-body p-0 striped">

        <div class="p-2">
            <h3 class="fs-5 float-end">Top developers</h3>
            <ul class="list-unstyled">
                @foreach ($developers as $developer)
                    <li>
                        <a class="d-inline-block" href="{{ route('games.search', ['developer_id' => $developer->pub_dev_id]) }}">
                            {{ $developer->pub_dev_name }}
                        </a>: {{ $developer->game_count }} games
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="p-2">
            <h3 class="fs-5 float-end">Top publishers</h3>
            <ul class="list-unstyled">
                @foreach ($publishers as $publisher)
                    <li>
                        <a class="d-inline-block" href="{{ route('games.search', ['publisher_id' => $publisher->pub_dev_id]) }}">
                            {{ $publisher->pub_dev_name }}
                        </a>: {{ $publisher->release_count }} releases
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="p-2">
            <h3 class="fs-5 float-end">Top genres</h3>
            <ul class="list-unstyled">
                @foreach ($genres as $genre)
                    <li>
                        <a class="d-inline-block" href="{{ route('games.search', ['genre_id' => $genre->id ]) }}">
                            {{ $genre->name }}
                        </a>: {{ $genre->game_count }} games
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="p-2">
            <h3 class="fs-5 float-end">Top individuals</h3>
            <ul class="list-unstyled">
                @foreach ($individuals as $individual)
                    <li>
                        <a class="d-inline-block" href="{{ route('games.search', ['individual_id' => $individual->ind_id ]) }}">
                            {{ $individual->ind_name }}
                        </a>: {{ $individual->game_count }} games
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
