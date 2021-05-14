@extends('layouts.app')
@section('title', $review->games->first()->game_name.' review (Atari ST)')

@if ($review->screenshots->isNotEmpty())
    @section('image', asset('storage/images/game_screenshots/'.$review->screenshots->first()->screenshot->file))
@endif

@section('content')
    <h1 class="visually-hidden">{{ $review->games->first()->game_name }}</h1>
    <div class="row">
        <div class="col-12 col-sm-6 col-lg-3 order-2 order-lg-1">
            @include('reviews.card_author', ['user' => $review->user, 'reviews' => $otherReviews])
            <x-cards.a-l-mobile />
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
