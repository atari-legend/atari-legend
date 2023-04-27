@extends('admin.layouts.admin')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.games.games.releases.scene.card_edit')
        </div>
    </div>
@endsection
