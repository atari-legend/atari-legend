@extends('admin.layouts.admin')
@section('title', 'Series - ' . (isset($series) ? $series->name : 'Create new series'))

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.games.series.card_edit')
        </div>
    </div>
    @isset ($series)
        <div class="row">
            <div class="col">
                @include('admin.games.series.card_games')
            </div>
        </div>
    @endif
@endsection
