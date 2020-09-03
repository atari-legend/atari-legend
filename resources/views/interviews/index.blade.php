@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-3 pl-0">
            <x-cards.latest-comments />
        </div>
        <div class="col-6">
            @include('interviews.card_list')
        </div>
        <div class="col-3 pr-0">
            <x-cards.link />
            <x-cards.trivia />
        </div>
    </div>
@endsection
