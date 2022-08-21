@extends('admin.layouts.admin')
@section('title', 'Magazine IndexTypes')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.magazines.index-types.card_add')
        </div>
    </div>
    <div class="row">
        <div class="col">
            @include('admin.magazines.index-types.card_list')
        </div>
    </div>
@endsection
