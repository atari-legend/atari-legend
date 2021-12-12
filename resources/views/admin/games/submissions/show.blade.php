@extends('admin.layouts.admin')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.games.submissions.card_show')
        </div>
    </div>
@endsection
