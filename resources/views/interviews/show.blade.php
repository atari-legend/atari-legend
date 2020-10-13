@extends('layouts.app')
@section('title', 'Interview with '.$interview->individual->ind_name)

@section('content')
    <h1 class="sr-only">{{ $interview->individual->ind_name }}</h1>
    <div class="row">
        <div class="col-12 col-sm-6 col-lg-3 order-2 order-lg-1">
            @include('interviews.card_intro')
            @include('interviews.card_profile')
            @include('interviews.card_credits')

        </div>
        <div class="col-12 col-lg-6 order-1 order-lg-2">
            @include('interviews.card_interview')
        </div>
        <div class="col col-sm-6 col-lg-3 order-3">
            @include('interviews.card_comments')
            @include('interviews.card_latest_interviews')
        </div>
    </div>
@endsection
