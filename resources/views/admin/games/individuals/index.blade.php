@extends('admin.layouts.admin')
@section('title', 'Individuals')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.games.individuals.card_list')
        </div>
    </div>
@endsection
