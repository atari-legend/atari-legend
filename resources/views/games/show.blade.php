@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-3 pl-0">
            @include('games.card_gameinfo')
            @include('games.card_releases')
            COMMENTS
        </div>
        <div class="col-6">
            @include('games.card_screenshots')
            REVIEWS<br>
            SUBMIT INFO<br>
        </div>
        <div class="col-3 pr-0">
            BOXSCAN
        </div>
    </div>
@endsection
