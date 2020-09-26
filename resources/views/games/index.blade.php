@extends('layouts.app')
@section('title', 'Search and browse Atari ST games')

@section('content')
    <div class="row">
        <div class="col-3 pl-0">
            <x-cards.latest-comments />
        </div>
        <div class="col-6">
            @include('games.card_search')
        </div>
        <div class="col-3 pr-0">
            @include('games.card_updates')
        </div>
    </div>
@endsection
