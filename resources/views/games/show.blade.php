@extends('layouts.app')
@section('title', $game->game_name.' - Atari ST game')
@section('description', GameHelper::description($game))

@if ($game->screenshots->isNotEmpty())
    @section('image', $game->screenshots->random()->getUrl('game'))
@endif

@section('content')
    <h1 class="visually-hidden">{{ $game->game_name }}</h1>
    <div class="row">
        <div class="col-12 col-sm-6 col-lg-3 lightbox-gallery">
            @include('games.card_gameinfo')
            @include('games.card_series')
            @if ($boxscans->isEmpty())
                @include('games.card_videos')
            @endif
            @include('games.card_boxscan')
            @include('games.card_magazines')
        </div>
        <div class="col-12 col-lg-6 order-sm-3 order-lg-2">
            @include('games.card_screenshots')
            @include('games.card_reviews')
            @include('games.card_menus')
        </div>
        <div class="col col-sm-6 col-lg-3 order-sm-2 order-lg-3">
            @include('games.card_releases')
            @include('games.card_music')
            @if ($boxscans->isNotEmpty())
                @include('games.card_videos')
            @endif
            @include('games.card_similar')
            @include('games.card_facts')
            @include('games.card_interviews')
            @include('games.card_comments')
            @include('games.card_submit_info')
        </div>
    </div>
@endsection

@section('scripts')
    @if ($sndhs->isNotEmpty())
        <script src="{{ mix('js/game-music.js') }}"></script>
    @endif
@endsection
