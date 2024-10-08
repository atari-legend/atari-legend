@extends('layouts.app')
@section('title', 'Search and browse Atari ST games')
@section('robots', 'follow,noindex')

@section('content')
    <h1 class="visually-hidden">Games search</h1>
    <div class="row">
        <div class="col-12 col-sm-6 col-lg-3 order-2 order-lg-1">
            <x-cards.latest-comments />
        </div>
        <div class="col-12 col-lg-6 order-1 order-lg-2">
            @include('games.card_search')
            <x-cards.tops />
        </div>
        <div class="col col-sm-6 col-lg-3 order-3">
            @include('games.card_updates')
            <x-cards.top-games />
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/charts.js'])    
@endsection
