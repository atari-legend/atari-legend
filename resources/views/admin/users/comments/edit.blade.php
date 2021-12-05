@extends('admin.layouts.admin')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.users.comments.card_edit')
        </div>
    </div>
@endsection
