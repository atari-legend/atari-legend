@extends('admin.layouts.admin')
@section('title', 'Article Types')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.articles.types.card_add')
        </div>
    </div>
    <div class="row">
        <div class="col">
            @include('admin.articles.types.card_list')
        </div>
    </div>
@endsection
