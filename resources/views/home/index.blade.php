@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-3 pl-0">
        <x-cards.screenstar />
        @include('home.card_altv')
        <x-cards.social />
        @include('home.card_open')
    </div>
    <div class="col-6">
        <div class="row">
            <div class="col">
                @include('home.card_carousel')
            </div>
        </div>
        <div class="row">
            <div class="col">
                @include('home.card_news')
                @include('home.card_stats')
            </div>
            <div class="col">
                @include('home.card_reviews')
                <x-cards.a-l-mobile />
                <x-cards.about />
            </div>
        </div>
    </div>
    <div class="col-3 pr-0">
        <x-cards.interview />
        <x-cards.link />
        <x-cards.trivia />
        @include('home.card_spotlight')
    </div>
</div>
@endsection
