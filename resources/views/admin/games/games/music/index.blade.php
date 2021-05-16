@extends('admin.layouts.admin')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.games.games.nav')
        </div>
    </div>
    <div class="row">
        <div class="col">
            @include('admin.games.games.music.card_list')
        </div>
    </div>
    <div class="row">
        <div class="col">
            @include('admin.games.games.music.card_add')
        </div>
        <div class="col">
            @include('admin.games.games.music.card_associate')
        </div>
    </div>
@endsection
