@extends('admin.layouts.admin')
@section('title', isset($link) ? $link->name : 'Create link')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.links.links.card_edit')
        </div>
    </div>
@endsection
