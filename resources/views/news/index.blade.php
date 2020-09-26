@extends('layouts.app')
@section('title', 'Latest Atari ST news')

@section('content')
    <div class="row">
        <div class="col-3 pl-0">
            @include('news.card_submit')
            <x-cards.about />
        </div>
        <div class="col-6">
            @include('news.card_list')
        </div>
        <div class="col-3 pr-0">
            <x-cards.screenstar />
            <x-cards.trivia />
        </div>
    </div>
@endsection
