@extends('admin.layouts.admin')

@section('title', 'Statistics')

@section('scripts')
    @vite(['resources/js/charts.js'])
@endsection

@section('content')
    <nav class="nav nav-pills sticky-top bg-white border-bottom mb-3 py-2">
        <a class="nav-link" href="#coverage">Coverage</a>
        <a class="nav-link" href="#activity">Activity</a>
        <a class="nav-link" href="#catalogue">Catalogue</a>
        <a class="nav-link" href="#community">Community</a>
        <a class="nav-link" href="#counts">All counts</a>
    </nav>

    @include('admin.home.card_stats', ['stats' => $headlines])
    @include('admin.others.statistics.card_coverage')
    @include('admin.others.statistics.card_activity')
    @include('admin.others.statistics.card_catalogue')
    @include('admin.others.statistics.card_community')
    @include('admin.others.statistics.card_counts')
@endsection
