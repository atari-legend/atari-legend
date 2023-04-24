@extends('admin.layouts.admin')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.games.games.releases.card_edit')
        </div>
    </div>
    @isset ($release)
        <div class="row">
            <div class="col">
                @include('admin.games.games.releases.card_edit_aka')
            </div>
        </div>
    @endif
@endsection
