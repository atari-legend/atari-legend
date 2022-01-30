<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Tops</h2>
    </div>
    <div class="card-body p-0 striped">

        <div class="p-0 row g-0 row-cols-1 row-cols-md-2">

            <div class="col p-2 border border-dark border-top-0">
                <h3 class="fs-5 text-center">Developers</h3>
                <table class="table table-sm table-borderless">
                    <tbody>
                        @foreach ($developers as $developer)
                            <tr>
                                <td class="text-muted text-nowrap" style="width: 2rem">{{ $loop->index+1 }}.</td>
                                <td class="text-nowrap" style="width: 45%;">
                                    <a href="{{ route('games.search', ['developer_id' => $developer->pub_dev_id]) }}">
                                        {{ $developer->pub_dev_name }}
                                    </a>
                                </td>
                                <td>{{ $developer->game_count }} games</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="col p-2 border border-dark border-top-0">
                <h3 class="fs-5 text-center">Publishers</h3>
                <table class="table table-sm table-borderless">
                    <tbody>
                        @foreach ($publishers as $publisher)
                            <tr>
                                <td class="text-muted text-nowrap" style="width: 2rem">{{ $loop->index+1 }}.</td>
                                <td class="text-nowrap" style="width: 45%;">
                                    <a href="{{ route('games.search', ['publisher_id' => $publisher->pub_dev_id]) }}">
                                        {{ $publisher->pub_dev_name }}
                                    </a>
                                </td>
                                <td>{{ $publisher->release_count }} games</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="col p-2 border border-dark">
                <h3 class="fs-5 text-center">Genres</h3>
                <table class="table table-sm table-borderless">
                    <tbody>
                        @foreach ($genres as $genre)
                            <tr>
                                <td class="text-muted text-nowrap" style="width: 2rem">{{ $loop->index+1 }}.</td>
                                <td class="text-nowrap" style="width: 45%;">
                                    <a href="{{ route('games.search', ['genre_id' => $genre->id]) }}">
                                        {{ $genre->name }}
                                    </a>
                                </td>
                                <td>{{ $genre->game_count }} games</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="col p-2 border border-dark">
                <h3 class="fs-5 text-center">Individuals</h3>
                <table class="table table-sm table-borderless">
                    <tbody>
                        @foreach ($individuals as $individual)
                            <tr>
                                <td class="text-muted text-nowrap" style="width: 2rem">{{ $loop->index+1 }}.</td>
                                <td class="text-nowrap" style="width: 45%;">
                                    <a href="{{ route('games.search', ['individual_id' => $individual->ind_id]) }}">
                                        {{ $individual->ind_name }}
                                    </a>
                                </td>
                                <td>{{ $individual->game_count }} games</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
