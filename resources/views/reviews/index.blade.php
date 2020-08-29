@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-3 pl-0">
            <x-cards.a-l-mobile />
        </div>
        <div class="col-6">
            @include('reviews.card_list')
        </div>
        <div class="col-3 pr-0">
            <x-cards.interview />
            <x-cards.trivia />
        </div>
    </div>
@endsection
