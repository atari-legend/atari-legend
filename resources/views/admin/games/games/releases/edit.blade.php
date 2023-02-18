@extends('admin.layouts.admin')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.games.games.releases.card_edit')
        </div>
    </div>
@endsection
