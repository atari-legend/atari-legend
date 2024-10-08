@extends('layouts.app')
@section('title', $magazine->name . ' - Atari ST magazine')

@section('content')
    <h1 class="visually-hidden">{{ $magazine->name }}</h1>
    <div class="row">
        <div class="col-12 col-sm-6 col-lg-3 order-2 order-lg-1">
            <x-cards.random-magazine-issue />
            <x-cards.trivia />
        </div>
        <div class="col-12 col-lg-6 order-1 order-lg-2">
            @include('magazines.card_issues')
        </div>
        <div class="col col-sm-6 col-lg-3 order-3">
            @include('magazines.card_page_count')
            <x-cards.latest-magazine-issues />
        </div>
    </div>
@endsection

@section('scripts')
    @vite('resources/js/charts.js')    
@endsection
