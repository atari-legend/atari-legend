@extends('layouts.app')
@section('title', 'Atari ST games search results')

@section('content')
    <div class="row">
        <div class="col-3 pl-0">
            <x-cards.latest-comments />
        </div>
        <div class="col-6">
            @include('games.card_search_results')
        </div>
        <div class="col-3 pr-0">
            <x-cards.screenstar />
        </div>
    </div>
@endsection
