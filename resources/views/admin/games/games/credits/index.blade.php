@extends('admin.layouts.admin')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.games.games.nav')
        </div>
    </div>
    <div class="row">
        <div class="col">
            @include('admin.games.games.credits.card_list')
        </div>
    </div>
    <div class="row">
        <div class="col">
            @include('admin.games.games.credits.card_developers')
        </div>
    </div>
@endsection
