@extends('admin.layouts.admin')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.games.games.nav')
        </div>
    </div>
    <div class="row">
        <div class="col">
            @include('admin.games.games.screenshots.card_list')
        </div>
    </div>
@endsection
