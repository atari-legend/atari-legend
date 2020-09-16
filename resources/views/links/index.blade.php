@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-3 pl-0">
            @include('links.card_submit')
            <x-cards.a-l-mobile />
        </div>
        <div class="col-6">
            @include('links.card_links')
        </div>
        <div class="col-3 pr-0">
            <x-cards.screenstar />
            <x-cards.trivia />
        </div>
    </div>
@endsection
