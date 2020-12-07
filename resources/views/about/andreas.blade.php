@extends('layouts.app')
@section('title', 'In loving memory of Andreas Wahlin')

@section('content')
    <h1 class="visually-hidden">Andreas Wahlin</h1>
    <div class="row">
        <div class="col-3 ps-0">
            @include('about.card_memory')
        </div>
        <div class="col-6">
            @include('about.card_andreas')
        </div>
        <div class="col-3 pe-0">
            @include('about.card_intro')
            <x-cards.a-l-mobile />
            <x-cards.social />
        </div>
    </div>
@endsection
