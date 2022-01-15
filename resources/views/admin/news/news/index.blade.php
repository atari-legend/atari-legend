@extends('admin.layouts.admin')
@section('title', 'News')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.news.news.card_list')
        </div>
    </div>
@endsection
