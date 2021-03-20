@extends('admin.layouts.admin')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.menus.disks.content.card_edit')
        </div>
    </div>
@endsection
