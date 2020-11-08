@extends('layouts.app')
@section('title', 'Profile')

@section('content')
<h1 class="visually-hidden">Profile</h1>
<div class="row">
    <div class="col-12 col-sm-6 col-lg-3 order-2 order-lg-1">
        <x-cards.trivia />
        <x-cards.about />
    </div>
    <div class="col-12 col-lg-6 order-1 order-lg-2">
        @include('auth.card_profile')
    </div>
    <div class="col col-sm-6 col-lg-3 order-3">
        <x-cards.statistics />
        <x-cards.social />
    </div>
</div>
@endsection
