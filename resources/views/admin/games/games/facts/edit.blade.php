@extends('admin.layouts.admin')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.games.games.facts.card_edit')
        </div>
    </div>
@endsection
