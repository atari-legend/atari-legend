@extends('admin.layouts.admin')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.users.users.card_edit')
        </div>
    </div>
    <div class="row">
        <div class="col">
            @include('admin.users.users.card_activity')
        </div>
    </div>
@endsection
