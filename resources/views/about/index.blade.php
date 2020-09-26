@extends('layouts.app')
@section('title', 'The history of Atari Legend')

@section('content')
    <div class="row">
        <div class="col-3 pl-0">
            @include('about.card_credits')
            <x-cards.social />
            @include('about.card_memory')
        </div>
        <div class="col-6">
            @include('about.card_history')
        </div>
        <div class="col-3 pr-0">
            @include('about.card_intro')
            <x-cards.a-l-mobile />
        </div>
    </div>
@endsection
