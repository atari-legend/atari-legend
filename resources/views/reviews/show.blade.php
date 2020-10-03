@extends('layouts.app')
@section('title', $review->games->first()->game_name.' review (Atari ST)')

@section('content')
    <div class="row">
        <div class="col-12 col-sm-6 col-lg-3 order-2 order-lg-1">
            @include('reviews.card_author', ['user' => $review->user, 'reviews' => $otherReviews])
        </div>
        <div class="col-12 col-lg-6 order-1 order-lg-2">
            @include('reviews.card_review')
        </div>
        <div class="col col-sm-6 col-lg-3 order-3">
            @include('reviews.card_comments')
            <x-cards.reviews />
        </div>
    </div>
@endsection
