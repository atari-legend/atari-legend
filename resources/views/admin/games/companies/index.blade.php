@extends('admin.layouts.admin')
@section('title', 'Companies')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.games.companies.card_list')
        </div>
    </div>
@endsection
