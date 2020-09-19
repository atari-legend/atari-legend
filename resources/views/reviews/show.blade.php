@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-3 pl-0">
            @include('reviews.card_author', ['user' => $review->user, 'reviews' => $otherReviews])
        </div>
        <div class="col-6">
            @include('reviews.card_review')
        </div>
        <div class="col-3 pr-0">
            @include('reviews.card_comments')
            <x-cards.reviews />
        </div>
    </div>
@endsection
