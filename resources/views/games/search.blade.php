@extends('layouts.app')
@section('title', 'Atari ST games search results')

@section('content')
    <h1 class="visually-hidden">Games search results</h1>
    <div class="row">
        <div class="col-12 col-sm-6 col-lg-3 order-2 order-lg-1">
            <x-cards.latest-comments />
        </div>
        <div class="col-12 col-lg-6 order-1 order-lg-2">
            @if ($export)
                @include('games.card_search_results_export')
            @else
                @include('games.card_search_results')
            @endif
        </div>
        <div class="col col-sm-6 col-lg-3 order-3">
            <x-cards.screenstar />
        </div>
    </div>
@endsection

@section('scripts')
    @if ($export)
        <script src="{{ mix('js/tabulator.js') }}"></script>
    @endif
@endsection
