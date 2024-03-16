@extends('admin.layouts.admin')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.games.games.releases.system.card_system_info')
        </div>
    </div>
    <div class="row">
        <div class="col">
            @include('admin.games.games.releases.system.card_system_enhancements')
            @include('admin.games.games.releases.system.card_memory')
            @include('admin.games.games.releases.system.card_memory_enhancements')
        </div>
        <div class="col">
            @include('admin.games.games.releases.system.card_tos')
            @include('admin.games.games.releases.system.card_disk_protection')
            @include('admin.games.games.releases.system.card_copy_protection')
        </div>
    </div>
    <div class="row">
        <div class="col">

        </div>
        <div class="col">

        </div>
    </div>
    <div class="row">
        <div class="col">

        </div>
        <div class="col">

        </div>
    </div>
@endsection
