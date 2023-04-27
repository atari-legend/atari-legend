@extends('admin.layouts.admin')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.games.games.releases.system.card_edit')
        </div>
    </div>
@endsection
