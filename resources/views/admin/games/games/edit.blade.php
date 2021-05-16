@extends('admin.layouts.admin')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.games.games.nav')
        </div>
    </div>
    <div>
        <div class="col">
            @include('admin.games.games.card_edit')
        </div>
    </div>
@endsection
