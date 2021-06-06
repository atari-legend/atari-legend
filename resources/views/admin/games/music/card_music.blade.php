<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title">Music/Game association</h2>

        <p class="card-text">
            This page helps associating games with songs from the SNDH archive.
            Games that don't have songs associated yet are
            randomly selected and for each one possible music matches are proposed.<br>
            Tick the songs that actually match the game and submit at the bottom
            to associate games with songs.<br>
            Songs title that exactly match the game name are already pre-ticked.
        </p>

        <form action="{{ route('admin.games.music.associate') }}" method="post">
            @csrf
            <table class="table table-striped">
            <thead>
                <tr>
                    <th>Game</th>
                    <th>Credits</th>
                    <th>Music matches</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($matches as $match)
                    @php
                        $game = $match['game'];
                        $sndhs = $match['sndhs'];
                    @endphp
                    @if (count($sndhs) > 0)
                        <tr>
                            <td>
                                <a href="{{ route('admin.games.games.edit', $game) }}">{{ $game->game_name }}</a>
                                <small class="text-muted ms-2">{{ $game->genres->pluck('name')->join(', ')}}</small>
                                @if ($game->screenshots->isNotEmpty())
                                    <img src="{{ $game->screenshots->random()->getUrl('game') }}"
                                        class="d-block mt-2"
                                        style="width: 10rem;">
                                @endif
                            </td>
                            <td>
                                <ul class="list-unstyled">
                                    @foreach ($game->individuals->sortBy('pivot.role.name') as $individual)
                                        <li>
                                            {{ $individual->ind_name }}
                                            @if ($individual->aka_list->isNotEmpty())
                                                <small>aka. {{ $individual->aka_list->join(', ') }}</small>
                                            @endif
                                            @if ($individual->pivot->role !== null)
                                                <span class="text-muted">[{{ $individual->pivot->role->name }}]</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                            <td>
                                @foreach ($sndhs as $sndh)
                                    <div>
                                        <input type="checkbox"
                                            id="{{ $game->game_id }}:{{ $sndh->id }}"
                                            @if (strtolower(trim($game->game_name)) === strtolower(trim($sndh->title))) checked @endif
                                            name="associations[]"
                                            value="{{ $game->game_id }}:{{ $sndh->id }}"
                                            class="form-check-input">
                                        <label class="form-check-lable" for="{{ $game->game_id }}:{{ $sndh->id }}">
                                            {{ $sndh->title}} <small class="text-muted ms-2">{{ $sndh->id }}</small>
                                        </label>
                                    </div>
                                @endforeach
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
            </table>

            <button type="submit" class="btn btn-success">Associate selected songs</button>
            <a href="{{ route('admin.games.music') }}" class="btn btn-link">Discard and generate a new selection</a>
        </form>

    </div>
</div>
