@extends('admin.layouts.admin')
@section('title', "Review - ".(isset($review) ? $review->games[0]->game_name : 'Create review'))

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.reviews.reviews.card_edit')
        </div>
    </div>
@endsection
