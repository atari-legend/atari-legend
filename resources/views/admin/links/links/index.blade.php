@extends('admin.layouts.admin')
@section('title', 'Links')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.links.links.card_list')
        </div>
    </div>
@endsection
