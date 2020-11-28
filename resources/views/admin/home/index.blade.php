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

    <div class="row">
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
