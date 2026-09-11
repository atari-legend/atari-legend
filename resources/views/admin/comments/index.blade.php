@extends('admin.layouts.admin')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.comments.card_list')
        </div>
    </div>
@endsection
