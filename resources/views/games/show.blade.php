@extends('layouts.app')
@section('title', $game->game_name.' - Atari ST game')

@section('content')
    <div class="row">
        <div class="col-3 pl-0">
            @include('games.card_gameinfo')
            @include('games.card_releases')
            @include('games.card_series')
            @include('games.card_comments')
        </div>
        <div class="col-6">
            @include('games.card_screenshots')
            @include('games.card_reviews')
        </div>
        <div class="col-3 pr-0">
            @include('games.card_boxscan')
            @include('games.card_similar')
            @include('games.card_facts')
            @include('games.card_interviews')
            @include('games.card_submit_info')

        </div>
    </div>
@endsection
