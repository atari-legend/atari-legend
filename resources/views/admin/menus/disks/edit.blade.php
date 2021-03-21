@extends('admin.layouts.admin')

@section('content')
    <div class="row">
        <div class="col-12 @isset($disk) col-xl-8 @endif">
            @include('admin.menus.disks.card_edit')
        </div>
        @isset ($disk)
            <div class="col-12 col-xl-4">
                @include('admin.menus.disks.card_screenshots')
                @include('admin.menus.disks.card_dump')
            </div>
        @endif
    </div>
    @isset ($disk)
        <div class="row">
            <div class="col">
                @include('admin.menus.disks.card_content')
            </div>
        </div>
    @endif
@endsection
