@extends('admin.layouts.admin')
@section('title', 'Quotes')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.others.quotes.card_add')
        </div>
    </div>
    <div class="row">
        <div class="col">
            @include('admin.others.quotes.card_list')
        </div>
    </div>
@endsection
