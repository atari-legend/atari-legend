@extends('admin.layouts.admin')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.menus.content-types.card_list')
        </div>
    </div>
@endsection
