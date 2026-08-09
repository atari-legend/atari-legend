@extends('admin.layouts.admin')

@section('title', 'Changelog')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.others.changelog.card_list')
        </div>
    </div>
@endsection
