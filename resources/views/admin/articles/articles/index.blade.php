@extends('admin.layouts.admin')
@section('title', 'Articles')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.articles.articles.card_list')
        </div>
    </div>
@endsection
