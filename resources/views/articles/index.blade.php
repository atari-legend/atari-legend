@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-3 pl-0">
            <x-cards.screenstar />
        </div>
        <div class="col-6">
            @include('articles.card_list')
        </div>
        <div class="col-3 pr-0">
            <x-cards.about />
            <x-cards.link />
        </div>
    </div>
@endsection
