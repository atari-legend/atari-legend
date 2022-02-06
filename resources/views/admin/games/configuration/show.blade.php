@extends('admin.layouts.admin')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.games.configuration.nav')
        </div>
    </div>
    <div class="row">
        <div class="col">
            @include('admin.games.configuration.card_add')
        </div>
    </div>
    <div class="row">
        <div class="col">
            @include('admin.games.configuration.card_configuration', ['type' => $type, 'label' => $label])
        </div>
    </div>
@endsection
