@extends('admin.layouts.admin')

@section('content')
    <div class="row">
        <div class="col-12 @isset($crew) col-xl-8 @endif">
            @include('admin.menus.crews.card_edit')
        </div>
        @isset ($crew)
            <div class="col-12 col-xl-4">
                @include('admin.menus.crews.card_logo')
            </div>
        @endif
    </div>
    @isset ($crew)
        <div class="row">
            <div class="col-12 col-lg-6">
                @include('admin.menus.crews.card_individuals')
            </div>
            <div class="col-12 col-lg-6">
                @include('admin.menus.crews.card_genealogy')
            </div>
        </div>
    @endif
@endsection
