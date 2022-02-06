@extends('admin.layouts.admin')
@section('title', 'Did you know?')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.others.trivias.card_add')
        </div>
    </div>
    <div class="row">
        <div class="col">
            @include('admin.others.trivias.card_list')
        </div>
    </div>
@endsection
