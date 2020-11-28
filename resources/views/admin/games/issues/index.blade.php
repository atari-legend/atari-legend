@extends('admin.layouts.admin')

@section('breadcrumbs')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.home.index') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="#">Games</a></li>
            <li class="breadcrumb-item active" aria-current="page">Issues</li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="row">
        <div class="col-12 col-md-6">
            @include('admin.games.issues.card_games_without_release')
            @include('admin.games.issues.card_games_without_screenshot')
        </div>
        <div class="col-12 col-md-6">
            @include('admin.games.issues.card_game_without_genres')
        </div>
    </div>
@endsection
