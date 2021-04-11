@extends('layouts.app')

@section('content')
    <h1 class="visually-hidden">AtariLegend home page</h1>
    <div class="row">
        <div class="col order-2 order-lg-1">
            <x-cards.screenstar />
            <x-cards.a-l-t-v-feed />
            @include('home.card_open')
        </div>
        <div class="col-12 col-lg-6 order-1 order-lg-2">
            <div class="row d-none d-sm-flex">
                <div class="col-12">
                    @include('home.card_carousel')
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-sm-6">
                    @include('home.card_news')
                    <x-cards.statistics />
                </div>
                <div class="col-12 col-sm-6">
                    <x-cards.reviews />
                    <x-cards.a-l-mobile />
                    <x-cards.about />
                </div>
            </div>
        </div>
        <div class="col order-3">
            <x-cards.interview />
            <x-cards.link />
            <x-cards.social />
            <x-cards.trivia />
            @include('home.card_spotlight')
        </div>
    </div>
@endsection
