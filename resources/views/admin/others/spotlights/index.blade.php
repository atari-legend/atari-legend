@extends('admin.layouts.admin')
@section('title', 'Spotlights')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.others.spotlights.card_list')
        </div>
    </div>
@endsection
