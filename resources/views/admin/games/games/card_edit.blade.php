@isset($game)
    <p class="text-center text-muted">
        Please use the navigation on the left to edit {{ $game->name }}.
    </p>
@endif

@include('admin.games.games.card_edit_base_info')

@isset($game)
    @include('admin.games.games.card_edit_multiplayer')
    @include('admin.games.games.card_edit_aka')
    @include('admin.games.games.card_edit_vs')
@endif

