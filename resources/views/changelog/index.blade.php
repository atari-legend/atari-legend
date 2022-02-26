@extends('layouts.app')
@section('title', 'Atari Legend database changes')
@section('robots', 'follow,noindex')

@section('content')
    <h1 class="visually-hidden">Database changes</h1>
    <div class="row">
        <div class="col-12 col-sm-6 col-lg-3 order-2 order-lg-1">
            <x-cards.statistics />
            <x-cards.a-l-mobile />
        </div>
        <div class="col-12 col-lg-6 order-1 order-lg-2">
            @include('changelog.card_changelog')
        </div>
        <div class="col col-sm-6 col-lg-3 order-3">
            <x-cards.top-games />
        </div>
    </div>
@endsection
