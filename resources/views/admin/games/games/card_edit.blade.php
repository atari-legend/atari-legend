@isset($game)
    <p class="text-center text-muted">
        Main game editing is still done in the legacy CPANEL.
        <a href="{{ config('al.legacy.base_url').'/admin/games/games_detail.php?game_id='.$game->game_id }}">
            View CPANEL for {{ $game->game_name }}
        </a>.
    </p>

    <p class="text-center text-muted">
        Please use the navigation at the top to edit {{ $game->game_name }}.
    </p>
@endif


@include('admin.games.games.card_edit_base_info')

@isset($game)
    @include('admin.games.games.card_edit_multiplayer')
    @include('admin.games.games.card_edit_aka')
@endif

