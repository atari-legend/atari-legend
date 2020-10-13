@extends('layouts.app')
@section('title', 'Latest Atari ST news')

@section('content')
    <h1 class="sr-only">News</h1>
    <div class="row">
        <div class="col-12 col-sm-6 col-lg-3 order-2 order-lg-1">
            @include('news.card_submit')
            <x-cards.about />
        </div>
        <div class="col-12 col-lg-6 order-1 order-lg-2">
            @include('news.card_list')
        </div>
        <div class="col col-sm-6 col-lg-3 order-3">
            <x-cards.screenstar />
            <x-cards.trivia />
        </div>
    </div>
@endsection
