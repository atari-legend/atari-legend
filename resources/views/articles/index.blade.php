@extends('layouts.app')
@section('title', 'Latest Atari ST articles')

@section('content')
    <h1 class="visually-hidden">Articles</h1>
    <div class="row">
        <div class="col-12 col-sm-6 col-lg-3 order-2 order-lg-1">
            <x-cards.screenstar />
        </div>
        <div class="col-12 col-lg-6 order-1 order-lg-2">
            @include('articles.card_list')
        </div>
        <div class="col col-sm-6 col-lg-3 order-3">
            <x-cards.about />
            <x-cards.link />
        </div>
    </div>
@endsection
