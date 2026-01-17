@extends('admin.layouts.admin')
@section('title', 'Interviews')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.interviews.interviews.card_list')
        </div>
    </div>
@endsection
