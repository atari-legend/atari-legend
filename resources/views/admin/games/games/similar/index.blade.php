@extends('admin.layouts.admin')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.games.games.nav')
        </div>
    </div>
    <div class="row">
        <div class="col">
            @include('admin.games.games.similar.card_list')
        </div>
    </div>
@endsection
