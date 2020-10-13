@extends('layouts.app')
@section('title', $game->game_name.' - Atari ST game')
@section('description', GameHelper::description($game))

@section('content')
    <h1 class="sr-only">{{ $game->game_name }}</h1>
    <div class="row">
        <div class="col-12 col-sm-6 col-lg-3 order-2 order-lg-1">
            @include('games.card_gameinfo')
            @include('games.card_releases')
            @include('games.card_series')
            @include('games.card_comments')
        </div>
        <div class="col-12 col-lg-6 order-1 order-lg-2">
            @include('games.card_screenshots')
            @include('games.card_reviews')
        </div>
        <div class="col col-sm-6 col-lg-3 order-3">
            @include('games.card_boxscan')
            @include('games.card_similar')
            @include('games.card_facts')
            @include('games.card_interviews')
            @include('games.card_submit_info')

        </div>
    </div>
@endsection
