@extends('layouts.app')
@section('title', 'Add a review for '.$game->first()->game_name.' (Atari ST)')

@section('content')
    <div class="row">
        <div class="col-12 col-sm-6 col-lg-3 order-2 order-lg-1">
            @include('reviews.card_author', ['user' => Auth::user(), 'reviews' => $otherReviews, 'mode' => 'submit'])
            <x-cards.reviews />
        </div>
        <div class="col-12 col-lg-6 order-1 order-lg-2">
            @include('reviews.card_submit')
        </div>
        <div class="col col-sm-6 col-lg-3 order-3">
            <x-cards.latest-comments :user="Auth::user()" />
        </div>
    </div>
@endsection
