@extends('layouts.app')
@section('title', 'The history of Atari Legend')

@section('content')
    <h1 class="sr-only">About</h1>
    <div class="row">
        <div class="col-12 col-sm-6 col-lg-3 ">
            @include('about.card_credits')
            <x-cards.social />
            @include('about.card_memory')
        </div>
        <div class="col-12 col-sm-6">
            @include('about.card_history')
        </div>
        <div class="col">
            @include('about.card_intro')
            <x-cards.a-l-mobile />
        </div>
    </div>
@endsection
