@extends('admin.layouts.admin')
@section('title', 'Link Categories')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.links.categories.card_list')
        </div>
    </div>
@endsection
