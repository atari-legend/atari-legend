@extends('admin.layouts.admin')
@section('title', 'Magazines')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.magazines.magazines.card_list')
        </div>
    </div>
@endsection
