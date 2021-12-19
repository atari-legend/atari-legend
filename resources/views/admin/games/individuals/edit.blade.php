@extends('admin.layouts.admin')
@section('title', "Individuals - ".(isset($individual) ? $individual->ind_name : 'Create new individual'))

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.games.individuals.card_edit')
        </div>
    </div>
    @if (isset($individual))
        <div class="row">
            <div class="col-12 col-md-6">
                @include('admin.games.individuals.card_nicknames')
            </div>
            <div class="col-12 col-md-6">
                @include('admin.games.individuals.card_credits')
            </div>
        </div>
    @endif
@endsection
