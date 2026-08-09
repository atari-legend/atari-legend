@extends('admin.layouts.admin')

@section('breadcrumbs')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active" aria-current="page">Home</li>
        </ol>
    </nav>
@endsection

@section('content')
    <p>Welcome back, {{ Auth::user()->userid }}.</p>

    @include('admin.home.card_stats')

    <div class="row">
        <div class="col">
            @include('admin.home.card_activity')
        </div>
    </div>
@endsection
