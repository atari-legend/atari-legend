@extends('layouts.app')
@section('title', 'In loving memory of Andreas Wahlin')

@section('content')
    <h1 class="sr-only">Andreas Wahlin</h1>
    <div class="row">
        <div class="col-3 pl-0">
            @include('about.card_memory')
        </div>
        <div class="col-6">
            @include('about.card_andreas')
        </div>
        <div class="col-3 pr-0">
            @include('about.card_intro')
            <x-cards.a-l-mobile />
            <x-cards.social />
        </div>
    </div>
@endsection
