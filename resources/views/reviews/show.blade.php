@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-3 pl-0">
            AUTHOR
        </div>
        <div class="col-6">
            @include('reviews.card_review')
        </div>
        <div class="col-3 pr-0">
            REVIEW_COMMENTS
            IN_DEPTH_REVIEWS
        </div>
    </div>
@endsection
