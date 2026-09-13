@extends('layouts.app')
@section('title', 'Play '.$disk->download_basename.' in your browser - Atari ST emulator')
@section('robots', 'follow,noindex')

@section('content')
    <h1 class="visually-hidden">{{ $disk->download_basename }}</h1>
    <div class="row">
        <div class="col-12 col-sm-6 col-lg-3 order-2 order-lg-1">
            <x-cards.latest-menus />
        </div>

        <div class="col-12 col-lg-6 order-1 order-lg-2">
            @include('emulator.card')
        </div>

        <div class="col-12 col-sm-6 col-lg-3 order-3">
            <x-cards.menu-disk :id="$disk->id" :play-overlay="false" />
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/game/emulator.js'])
@endsection
