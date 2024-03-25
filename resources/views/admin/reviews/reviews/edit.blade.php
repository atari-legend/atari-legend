@extends('admin.layouts.admin')
@section('title', "Review - ".(isset($review) ? $review->games->first()->game_name : 'Create review'))

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.reviews.reviews.card_edit')
        </div>
    </div>
    <div class="row">
        <div class="col">
            @include('admin.reviews.reviews.card_images')
        </div>
    </div>
@endsection
