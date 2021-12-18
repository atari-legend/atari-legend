@extends('admin.layouts.admin')
@section('title', "Individuals - {$individual->ind_name}")

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.games.individuals.card_edit')
        </div>
    </div>
    <div class="row">
        <div class="col">
            @include('admin.games.individuals.card_nicknames')
        </div>
    </div>
@endsection
