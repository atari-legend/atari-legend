@extends('admin.layouts.admin')

@section('content')
    <div class="row">
        <div class="col-12 col-md-6">
            @include('admin.games.issues.card_games_without_release')
            @include('admin.games.issues.card_games_without_screenshot')
            @include('admin.games.issues.card_games_with_bad_slug')
            @include('admin.games.issues.card_releases_without_scans')
        </div>
        <div class="col-12 col-md-6">
            @include('admin.games.issues.card_game_without_genres')
        </div>
    </div>
@endsection
