@extends('admin.layouts.admin')
@section('title', "Companies - ".(isset($company) ? $company->pub_dev_name : 'Create new company'))

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.games.companies.card_edit')
        </div>
    </div>
    @if (isset($company))
        <div class="row">
            <div class="col-12 col-md-6">
                @include('admin.games.companies.card_developer')
            </div>
            <div class="col-12 col-md-6">
                @include('admin.games.companies.card_publisher')
            </div>
        </div>
    @endif
@endsection
