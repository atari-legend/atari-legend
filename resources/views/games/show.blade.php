@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-3 pl-0">
            @include('games.card_gameinfo')
            @include('games.card_releases')
            SERIES<br>
            @include('games.card_comments')
        </div>
        <div class="col-6">
            @include('games.card_screenshots')
            @include('games.card_reviews')
        </div>
        <div class="col-3 pr-0">
            @include('games.card_boxscan')
            SIMILAR<br>
            FACTS<br>
            INTERVIEW<br>
            SUBMIT INFO<br>

        </div>
    </div>
@endsection
