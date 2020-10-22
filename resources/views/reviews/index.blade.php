@extends('layouts.app')
@section('title', 'Latest Atari ST game reviews')

@section('content')
    <h1 class="visually-hidden">Reviews</h1>
    <div class="row">
        <div class="col-12 col-sm-6 col-lg-3 order-2 order-lg-1">
            <x-cards.a-l-mobile />
        </div>
        <div class="col-12 col-lg-6 order-1 order-lg-2">
            @include('reviews.card_list')
        </div>
        <div class="col col-sm-6 col-lg-3 order-3">
            <x-cards.interview />
            <x-cards.trivia />
        </div>
    </div>
@endsection
