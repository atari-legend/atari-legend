@extends('admin.layouts.admin')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.menus.software.card_list')
        </div>
    </div>
@endsection
