@extends('layouts.app')
@section('title', 'Latest Atari ST interviews')

@section('content')
    <h1 class="visually-hidden">Interviews</h1>
    <div class="row">
        <div class="col-12 col-sm-6 col-lg-3 order-2 order-lg-1">
            <x-cards.latest-comments />
        </div>
        <div class="col-12 col-lg-6 order-1 order-lg-2">
            @include('interviews.card_list')
        </div>
        <div class="col col-sm-6 col-lg-3 order-3">
            <x-cards.link />
            <x-cards.trivia />
        </div>
    </div>
@endsection
