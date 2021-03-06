@extends('admin.layouts.admin')

@section('content')
    <div class="row">
        <div class="col-12 col-xl-8">
            @include('admin.menus.disks.card_edit')
        </div>
        <div class="col-12 col-xl-4">
            @include('admin.menus.disks.card_screenshots')
        </div>
    </div>
    <div class="row">
        <div class="col">
            @include('admin.menus.disks.card_content')
        </div>
    </div>
@endsection
