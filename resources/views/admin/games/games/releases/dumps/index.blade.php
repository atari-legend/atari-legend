@extends('admin.layouts.admin')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.games.games.releases.dumps.card_list')
        </div>
    </div>
@endsection
