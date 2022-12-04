@extends('admin.layouts.admin')
@section('title', 'Series')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.games.series.card_list')
        </div>
    </div>
@endsection
