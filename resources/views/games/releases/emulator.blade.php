@extends('layouts.app')
@section('title', 'Play '.$release->game->name.' in your browser - Atari ST emulator')
@section('robots', 'follow,noindex')

@section('content')
    <h1 class="visually-hidden">{{ $release->game->name }}</h1>
    <div class="row">
        <div class="col-12 col-sm-6 col-lg-3 order-2 order-lg-1 lightbox-gallery">
            @include('games.releases.card_game')
            @include('games.card_releases', ['game' => $release->game, 'currentRelease' => $release])
        </div>

        <div class="col-12 col-lg-6 order-1 order-lg-2">
            @include('emulator.card')
        </div>

        <div class="col-12 col-sm-6 col-lg-3 order-3">
            @include('games.card_boxscan')
            @include('games.card_submit', ['game' => $release->game])
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/game/emulator.js'])
@endsection
