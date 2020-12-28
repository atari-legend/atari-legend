@extends('admin.layouts.admin')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.menus.menus.card_edit')
        </div>
    </div>
    @isset ($menu)
        <div class="row">
            <div class="col">
                @include('admin.menus.disks.card_list', ['disks' => $menu->disks ?? []])
            </div>
        </div>
    @endif
@endsection
