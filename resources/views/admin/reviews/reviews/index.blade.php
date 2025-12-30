@extends('admin.layouts.admin')
@section('title', 'Reviews')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.reviews.reviews.card_list')
        </div>
    </div>
@endsection
