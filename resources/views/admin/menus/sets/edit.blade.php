@extends('admin.layouts.admin')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.menus.sets.card_edit')
        </div>
    </div>
    @isset ($set)
        <div class="row">
            <div class="col">
                @include('admin.menus.menus.card_list', ['menus' => $set->menus()->paginate(20) ?? []])
            </div>
        </div>
    @endif
@endsection
