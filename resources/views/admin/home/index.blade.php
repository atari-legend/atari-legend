@extends('admin.layouts.admin')

@section('content')
    <h1>Welcome, {{ Auth::user()->userid }}.</h1>

    <div class="row mb-3">
        <div class="col">
            @include('admin.home.card_stat', [
                'count' => $gamesCount,
                'label' => "Games",
                'icon'  => "fas fa-gamepad"
            ])
        </div>
        <div class="col">
            @include('admin.home.card_stat', [
                'count' => $releasesCount,
                'label' => "Releases",
                'icon'  => "fas fa-database"
            ])
        </div>
        <div class="col">
            @include('admin.home.card_stat', [
                'count' => $individualsCount,
                'label' => "Individuals",
                'icon'  => "fas fa-user-tie"
            ])
        </div>
        <div class="col">
            @include('admin.home.card_stat', [
                'count' => $companiesCount,
                'label' => "Companies",
                'icon'  => "fas fa-building"
            ])
        </div>
        <div class="col">
            @include('admin.home.card_stat', [
                'count' => $usersCount,
                'label' => "Users",
                'icon'  => "fas fa-user-friends"
            ])
        </div>
    </div>
    <div class="row">
        <div class="col">
            @include('admin.home.card_activity')
        </div>
    </div>
@endsection
