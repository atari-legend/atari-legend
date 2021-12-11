@extends('admin.layouts.admin')
@section('title', 'Game submissions')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.games.submissions.card_list')
        </div>
    </div>
@endsection
