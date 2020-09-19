@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-3 pl-0">
            @include('reviews.card_author', ['user' => Auth::user(), 'reviews' => $otherReviews, 'mode' => 'submit'])
            <x-cards.reviews />
        </div>
        <div class="col-6">
            @include('reviews.card_submit')
        </div>
        <div class="col-3 pr-0">
            COMMENTS<br>
        </div>
    </div>
@endsection
