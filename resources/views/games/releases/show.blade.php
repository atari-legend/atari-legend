@extends('layouts.app')
@section('title', ($release->date ? $release->date->year : '').' release of '.$release->game->game_name.' - Atari ST game release')
@section('robots', 'follow,noindex')

@if ($release->game->screenshots->isNotEmpty())
    @section('image', $release->game->screenshots->random()->getUrlRoute('game', $release->game))
@endif

@section('content')
    <h1 class="visually-hidden">{{ $release->game->game_name }}</h1>
    <div class="row">
        <div class="col-12 col-sm-6 col-lg-3 order-2 order-lg-1 lightbox-gallery">
            @include('games.releases.card_game')
            @include('games.card_releases', ['game' => $release->game, 'currentRelease' => $release])
        </div>
        <div class="col-12 col-lg-6 order-1 order-lg-2">
            @include('games.releases.card_release')
            @include('games.releases.card_inthebox')
            @include('games.releases.card_media')
        </div>
        <div class="col-12 col-sm-6 col-lg-3 order-3">
            @include('games.card_boxscan')
            @include('games.card_submit_info', ['game' => $release->game])
        </div>
    </div>
@endsection
